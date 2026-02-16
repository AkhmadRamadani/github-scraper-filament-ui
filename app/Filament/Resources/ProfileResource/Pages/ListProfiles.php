<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Models\Profile;
use App\Models\ScrapeJob;
use App\Services\GitHubScraperService;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_cv')
                ->label('Import CV')
                ->icon('heroicon-o-document-arrow-up')
                ->form([
                    FileUpload::make('cv_file')
                        ->label('CV File (PDF)')
                        ->disk('public')
                        ->directory('cv-uploads')
                        ->visibility('public')
                        ->required()
                        ->acceptedFileTypes(['application/pdf']),
                ])
                ->action(function (array $data) {
                    $content = Storage::disk('public')->get($data['cv_file']);

                    try {
                        $response = Http::attach(
                            'file', $content, 'cv.pdf'
                        )->post('https://cvreader.ramashook.my.id/parse-cv');

                        if ($response->successful()) {
                            $json = $response->json();
                            $parsedData = $json['data'] ?? [];

                            $name = $parsedData['name'] ?? null;
                            $email = $parsedData['email'] ?? null;
                            $phone = $parsedData['phone'] ?? null;
                            $title = $parsedData['title'] ?? null;
                            $location = $parsedData['location'] ?? null;
                            $linkedinUrl = $parsedData['linkedin'] ?? null;

                            // Determine HTML URL
                            $html_url = $parsedData['website'] ?? $parsedData['github'] ?? $linkedinUrl ?? '#';

                            // Company
                            $company = null;
                            $workExperience = $parsedData['work_experience'] ?? [];
                            if (is_array($workExperience) && count($workExperience) > 0) {
                                $firstExp = $workExperience[0];
                                $company = $firstExp['company'] ?? null;
                            }

                            // Build Bio (Keep it simple now that we have structured fields)
                            $bioParts = [];
                            if ($title) {
                                $bioParts[] = "**{$title}**";
                            }
                            if (isset($parsedData['summary'])) {
                                $bioParts[] = $parsedData['summary'];
                            }

                            $bio = implode("\n\n", $bioParts);

                            // Find existing profile or create new one
                            $profile = null;
                            if ($email) {
                                $profile = Profile::where('email', $email)->first();
                            }

                            $updateData = [
                                'name' => $name,
                                'bio' => $bio,
                                'company' => $company,
                                'location' => $location,
                                'html_url' => ($html_url !== '#' && $html_url) ? $html_url : ($profile?->html_url ?? '#'),
                                'cv_file' => $data['cv_file'],
                                'technical_skills' => $parsedData['technical_skills'] ?? null,
                                'work_experience' => $parsedData['work_experience'] ?? null,
                                'education' => $parsedData['education'] ?? null,
                                'projects' => $parsedData['projects'] ?? null,
                                'certifications' => $parsedData['certifications'] ?? null,
                                'volunteering' => $parsedData['volunteering'] ?? null,
                                'phone' => $phone,
                                'linkedin_url' => $linkedinUrl,
                            ];

                            if ($profile) {
                                // Update existing profile
                                // Only update fields if they are present in the parsed data, or keep existing
                                $profile->update(array_filter($updateData, fn($value) => !is_null($value)));
                            } else {
                                // Generate login if missing
                                $login = null;
                                if ($email) {
                                    $login = explode('@', $email)[0];
                                } elseif ($name) {
                                    $login = Str::slug($name);
                                } else {
                                    $login = 'user-' . Str::random(8);
                                }

                                // Ensure login is unique
                                if (Profile::where('login', $login)->exists()) {
                                    $login = $login . '-' . Str::random(4);
                                }

                                $createData = array_merge($updateData, [
                                    'login' => $login,
                                    'scrape_job_id' => null,
                                    'avatar_url' => $name ? "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=200" : null,
                                ]);

                                $profile = Profile::create($createData);
                            }

                            $notification = Notification::make()
                                ->title('CV Imported Successfully')
                                ->success();

                            // Trigger GitHub Scraper if valid GitHub URL
                            $githubUsername = null;
                            $githubUrl = $parsedData['github'] ?? null;

                            // If not explicitly provided, check html_url
                            if (!$githubUrl && $profile->html_url && str_contains($profile->html_url, 'github.com')) {
                                $githubUrl = $profile->html_url;
                            }

                            if ($githubUrl) {
                                if (!str_starts_with($githubUrl, 'http')) {
                                    $githubUrl = 'https://' . $githubUrl;
                                }
                                $path = parse_url($githubUrl, PHP_URL_PATH);
                                if ($path) {
                                    $parts = explode('/', trim($path, '/'));
                                    $githubUsername = $parts[0] ?? null;
                                }
                            }

                            if ($githubUsername) {
                                try {
                                    $scrapeJob = ScrapeJob::create([
                                        'job_id' => Str::uuid()->toString(),
                                        'user_id' => auth()->id(),
                                        'username' => $githubUsername,
                                        'status' => 'pending',
                                        'progress' => 0,
                                    ]);

                                    $profile->update(['scrape_job_id' => $scrapeJob->id]);

                                    $service = app(GitHubScraperService::class);
                                    $scrapeResponse = $service->scrapeAsync(
                                        username: $githubUsername,
                                        webhookUrl: null
                                    );

                                    $scrapeJob->update([
                                        'job_id' => $scrapeResponse['job_id'],
                                        'status' => $scrapeResponse['status'],
                                    ]);

                                    $notification->body("CV Imported. GitHub scraping started for user: {$githubUsername}");
                                } catch (\Exception $e) {
                                    $notification->body("CV Imported, but failed to start GitHub scraping: " . $e->getMessage())
                                        ->warning();
                                }
                            }

                            $notification->send();

                        } else {
                            throw new \Exception('API Error: ' . $response->status() . ' - ' . $response->body());
                        }
                    } catch (\Exception $e) {
                         Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
