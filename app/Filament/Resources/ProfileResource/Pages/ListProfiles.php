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

                            // Determine GitHub Username
                            $githubUsername = null;
                            $githubUrl = $parsedData['github'] ?? null;

                            if (!$githubUrl && $html_url && str_contains($html_url, 'github.com')) {
                                $githubUrl = $html_url;
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

                            // Company
                            $company = null;
                            $workExperience = $parsedData['work_experience'] ?? [];
                            if (is_array($workExperience) && count($workExperience) > 0) {
                                $firstExp = $workExperience[0];
                                $company = $firstExp['company'] ?? null;
                            }

                            // Build Bio
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

                            // Determine Avatar URL
                            $avatarUrl = null;
                            if ($githubUsername) {
                                $avatarUrl = "https://github.com/{$githubUsername}.png";
                            } elseif ($name) {
                                $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&size=200";
                            }

                            $updateData = [
                                'name' => $name,
                                'bio' => $bio,
                                'company' => $company,
                                'location' => $location,
                                'html_url' => ($html_url !== '#' && $html_url) ? $html_url : ($profile?->html_url ?? '#'),
                                'cv_file' => $data['cv_file'],
                                'phone' => $phone,
                                'linkedin_url' => $linkedinUrl,
                                'avatar_url' => $avatarUrl,
                            ];

                            if ($profile) {
                                // Only update if avatar is not already set or if we have a better one (GitHub)
                                if ($profile->avatar_url && !$githubUsername) {
                                     unset($updateData['avatar_url']);
                                }
                                $profile->update(array_filter($updateData, fn($value) => !is_null($value)));
                            } else {
                                $login = null;
                                if ($githubUsername) {
                                    $login = $githubUsername;
                                } elseif ($email) {
                                    $login = explode('@', $email)[0];
                                } elseif ($name) {
                                    $login = Str::slug($name);
                                } else {
                                    $login = 'user-' . Str::random(8);
                                }

                                if (Profile::where('login', $login)->exists()) {
                                    $login = $login . '-' . Str::random(4);
                                }

                                $createData = array_merge($updateData, [
                                    'login' => $login,
                                    'scrape_job_id' => null,
                                ]);

                                $profile = Profile::create($createData);
                            }

                            // --- Update Related Tables ---

                            // 1. Experiences
                            $profile->experiences()->delete();
                            if (!empty($parsedData['work_experience']) && is_array($parsedData['work_experience'])) {
                                foreach ($parsedData['work_experience'] as $exp) {
                                    $profile->experiences()->create([
                                        'position' => $exp['position'] ?? $exp['title'] ?? null,
                                        'company' => $exp['company'] ?? $exp['organization'] ?? null,
                                        'start_date' => $exp['start_date'] ?? null,
                                        'end_date' => $exp['end_date'] ?? null,
                                        'location' => $exp['location'] ?? null,
                                        'responsibilities' => is_array($exp['responsibilities'] ?? null)
                                            ? implode("\n", $exp['responsibilities'])
                                            : ($exp['responsibilities'] ?? null),
                                    ]);
                                }
                            }

                            // 2. Education
                            $profile->educations()->delete();
                            if (!empty($parsedData['education']) && is_array($parsedData['education'])) {
                                foreach ($parsedData['education'] as $edu) {
                                    $profile->educations()->create([
                                        'institution' => $edu['institution'] ?? $edu['school'] ?? null,
                                        'degree' => $edu['degree'] ?? null,
                                        'start_date' => $edu['start_date'] ?? null,
                                        'end_date' => $edu['end_date'] ?? null,
                                        'location' => $edu['location'] ?? null,
                                    ]);
                                }
                            }

                            // 3. Projects
                            $profile->projects()->delete();
                            if (!empty($parsedData['projects']) && is_array($parsedData['projects'])) {
                                foreach ($parsedData['projects'] as $proj) {
                                    $profile->projects()->create([
                                        'name' => $proj['name'] ?? null,
                                        'description' => $proj['description'] ?? null,
                                        'url' => $proj['url'] ?? null,
                                    ]);
                                }
                            }

                            // 4. Skills
                            $profile->skills()->delete();
                            if (!empty($parsedData['technical_skills']) && is_array($parsedData['technical_skills'])) {
                                foreach ($parsedData['technical_skills'] as $category => $items) {
                                    if (is_array($items)) {
                                        foreach ($items as $item) {
                                            $profile->skills()->create([
                                                'category' => $category,
                                                'name' => $item,
                                            ]);
                                        }
                                    } elseif (is_string($items)) {
                                         // If it's just a simple key-value where value is string
                                        $profile->skills()->create([
                                            'category' => 'General',
                                            'name' => $items,
                                        ]);
                                    }
                                }
                            }

                            // 5. Certifications
                            $profile->certifications()->delete();
                            if (!empty($parsedData['certifications']) && is_array($parsedData['certifications'])) {
                                foreach ($parsedData['certifications'] as $cert) {
                                    $profile->certifications()->create([
                                        'name' => $cert['name'] ?? null,
                                        'issuer' => $cert['issuer'] ?? null,
                                        'date' => $cert['date'] ?? null,
                                    ]);
                                }
                            }

                            // 6. Volunteering
                            $profile->volunteerings()->delete();
                            if (!empty($parsedData['volunteering']) && is_array($parsedData['volunteering'])) {
                                foreach ($parsedData['volunteering'] as $vol) {
                                    // Handle if volunteering is just a list of strings
                                    if (is_string($vol)) {
                                         $profile->volunteerings()->create([
                                            'organization' => $vol,
                                            'description' => $vol,
                                        ]);
                                    } elseif (is_array($vol)) {
                                        $profile->volunteerings()->create([
                                            'organization' => $vol['organization'] ?? null,
                                            'role' => $vol['role'] ?? $vol['position'] ?? null,
                                            'start_date' => $vol['start_date'] ?? null,
                                            'end_date' => $vol['end_date'] ?? null,
                                            'description' => $vol['description'] ?? null,
                                        ]);
                                    }
                                }
                            }

                            $notification = Notification::make()
                                ->title('CV Imported Successfully')
                                ->success();

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
