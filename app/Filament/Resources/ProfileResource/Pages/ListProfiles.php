<?php

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Models\Profile;
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
                            $parsedData = $response->json();

                            $name = $parsedData['name'] ?? null;
                            $email = $parsedData['email'] ?? null;
                            $phone = $parsedData['phone'] ?? null;
                            $skills = $parsedData['skills'] ?? [];
                            if (is_array($skills)) {
                                $skills = implode(', ', $skills);
                            }
                            $summary = $parsedData['summary'] ?? $parsedData['objective'] ?? '';
                            $experience = $parsedData['experience'] ?? [];

                            $company = null;
                            if (is_array($experience) && count($experience) > 0) {
                                $firstExp = $experience[0];
                                // Handle if experience is string or array
                                if (is_array($firstExp)) {
                                     $company = $firstExp['company'] ?? $firstExp['organization'] ?? null;
                                }
                            } elseif (is_string($experience)) {
                                $company = $experience;
                            }

                            $bio = $summary;
                            if ($skills) {
                                $bio .= "\n\nSkills: " . $skills;
                            }
                            if ($phone) {
                                $bio .= "\n\nPhone: " . $phone;
                            }

                            // Generate login if missing
                            $login = $parsedData['login'] ?? null;
                            if (!$login) {
                                if ($email) {
                                    $login = explode('@', $email)[0];
                                } elseif ($name) {
                                    $login = Str::slug($name);
                                } else {
                                    $login = 'user-' . Str::random(8);
                                }
                            }

                            // Ensure login is unique
                             if (Profile::where('login', $login)->exists()) {
                                $login = $login . '-' . Str::random(4);
                             }

                            Profile::create([
                                'login' => $login,
                                'name' => $name,
                                'email' => $email,
                                'bio' => $bio,
                                'company' => $company,
                                'location' => $parsedData['location'] ?? null,
                                'html_url' => $parsedData['website'] ?? '#',
                                'cv_file' => $data['cv_file'],
                                'scrape_job_id' => null,
                            ]);

                            Notification::make()
                                ->title('CV Imported Successfully')
                                ->success()
                                ->send();

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
