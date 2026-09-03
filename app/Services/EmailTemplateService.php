<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateTranslation;
use Illuminate\Support\Facades\DB;

class EmailTemplateService
{
    public function save(array $attributes, ?EmailTemplate $template = null): EmailTemplate
    {
        return DB::transaction(function () use ($attributes, $template) {
            $template ??= new EmailTemplate();
            $template->fill([
                'name' => $attributes['name'],
                'action' => $attributes['action'],
                'subject' => $attributes['subject'],
                'body' => $attributes['body'],
                'type' => $attributes['type'],
                'status' => $attributes['status'],
            ]);
            $template->save();

            foreach ($attributes['translations'] ?? [] as $locale => $trans) {
                $subject = trim($trans['subject'] ?? '');
                $body = trim($trans['body'] ?? '');

                if ($subject === '' && $body === '') {
                    EmailTemplateTranslation::query()
                        ->where('email_template_id', $template->id)
                        ->where('locale', $locale)
                        ->delete();
                    continue;
                }

                EmailTemplateTranslation::query()->updateOrCreate(
                    ['email_template_id' => $template->id, 'locale' => $locale],
                    ['subject' => $subject ?: $template->subject, 'body' => $body ?: null],
                );
            }

            return $template->fresh();
        });
    }

    public function delete(EmailTemplate $template): void
    {
        $template->delete();
    }
}
