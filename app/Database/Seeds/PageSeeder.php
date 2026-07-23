<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('pages')->countAllResults() > 0) {
            return;
        }

        $timestamp = '2024-01-01 00:00:00';

        $pages = [
            [
                'slug'  => 'about-us',
                'title' => 'About Us',
                'body'  => '[PLACEHOLDER — replace before launch. This page must describe: '
                    . '(1) who publishes this site and its editorial mission, '
                    . '(2) the makeup of the editorial team (writers, editors, roles), '
                    . '(3) that some articles are AI-assisted and how that fits the editorial process, '
                    . '(4) contact information or a link to /contact-us, '
                    . '(5) company/legal entity name and founding date.]',
            ],
            [
                'slug'  => 'contact-us',
                'title' => 'Contact Us',
                'body'  => '[PLACEHOLDER — replace before launch. This page must include: '
                    . '(1) a real business mailing address, '
                    . '(2) a monitored contact/support email address, '
                    . '(3) a phone number if applicable, '
                    . '(4) a note on expected response time, '
                    . '(5) a separate email or method for reporting factual errors, linking to /corrections-policy.]',
            ],
            [
                'slug'  => 'editorial-policy',
                'title' => 'Editorial Policy',
                'body'  => '[PLACEHOLDER — replace before launch. This page must describe: '
                    . '(1) how AI is used to draft articles, '
                    . '(2) that every AI draft is reviewed, fact-checked, and explicitly approved by a named human editor before publication, '
                    . '(3) your fact-checking standards, '
                    . '(4) how corrections are handled (link to /corrections-policy).]',
            ],
            [
                'slug'  => 'corrections-policy',
                'title' => 'Corrections Policy',
                'body'  => '[PLACEHOLDER — replace before launch. This page must describe: '
                    . '(1) how readers can report a factual error, '
                    . '(2) the distinction between minor and substantial corrections, '
                    . '(3) the article_corrections logging mechanism used internally to record who made a correction, when, and why, and that a visible correction note is attached to the affected article, '
                    . '(4) how retracted articles are handled.]',
            ],
            [
                'slug'  => 'privacy-policy',
                'title' => 'Privacy Policy',
                'body'  => '[PLACEHOLDER — replace before launch. This is a placeholder only and is NOT a substitute for '
                    . 'a lawyer-reviewed privacy policy. Before launch, have counsel draft/review a policy covering: '
                    . '(1) data collected via Google AdSense and any ad partners, '
                    . '(2) analytics tools in use (e.g. Google Analytics) and what they collect, '
                    . '(3) cookies and tracking technologies, with a cookie consent mechanism if serving EU/UK/California users, '
                    . '(4) user rights under GDPR/CCPA as applicable, '
                    . '(5) data retention and third-party data sharing practices, '
                    . '(6) a contact method for privacy requests.]',
            ],
            [
                'slug'  => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'body'  => '[PLACEHOLDER — replace before launch. This page must describe: '
                    . '(1) acceptable use of the site and its content, '
                    . '(2) intellectual property/copyright terms for articles, images, and AI-generated content, '
                    . '(3) disclaimers of liability, '
                    . '(4) governing law/jurisdiction, '
                    . '(5) how these terms may change and how users will be notified. Have counsel review before launch.]',
            ],
        ];

        $rows = [];
        foreach ($pages as $page) {
            $rows[] = [
                'slug'             => $page['slug'],
                'title'            => $page['title'],
                'body_html'        => $page['body'],
                'meta_description' => null,
                'is_published'     => 0,
                'updated_by'       => null,
                'created_at'       => $timestamp,
                'updated_at'       => $timestamp,
            ];
        }

        $this->db->table('pages')->insertBatch($rows);
    }
}
