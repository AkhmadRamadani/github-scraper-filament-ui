<?php

namespace App\Exports;

use App\Models\ScrapeJob;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfileSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected ScrapeJob $scrapeJob;

    public function __construct(ScrapeJob $scrapeJob)
    {
        $this->scrapeJob = $scrapeJob;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $profile = $this->scrapeJob->profile;

        if (!$profile) {
            return [['No profile data available']];
        }

        return [
            [
                $profile->login,
                $profile->name,
                $profile->bio,
                $profile->company,
                $profile->location,
                $profile->email,
                $profile->blog,
                $profile->twitter_username,
                $profile->public_repos,
                $profile->public_gists,
                $profile->followers,
                $profile->following,
                $profile->follower_ratio,
                $profile->html_url,
                $profile->github_created_at?->format('Y-m-d H:i:s'),
                $profile->github_updated_at?->format('Y-m-d H:i:s'),
            ]
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Profile';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Username',
            'Name',
            'Bio',
            'Company',
            'Location',
            'Email',
            'Blog',
            'Twitter',
            'Public Repos',
            'Public Gists',
            'Followers',
            'Following',
            'Follower Ratio',
            'GitHub URL',
            'Account Created',
            'Last Updated',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ],
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 25,
            'C' => 50,
            'D' => 25,
            'E' => 20,
            'F' => 30,
            'G' => 30,
            'H' => 20,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 15,
            'N' => 40,
            'O' => 20,
            'P' => 20,
        ];
    }
}
