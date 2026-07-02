<?php

namespace Database\Seeders;

use Coderstm\Models\Notification;
use Coderstm\Traits\Helpers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class NotificationSeeder extends Seeder
{
    use Helpers;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $notifications = $this->loadNotificationTemplates();

        foreach ($notifications as $notification) {
            Notification::updateOrCreate([
                'type' => $notification['type'],
            ], $notification);
        }

        $this->migratePushTemplates();
    }

    /**
     * Migrate existing push:xxx template content into the text column
     * of corresponding user:xxx templates, then remove push:xxx records.
     */
    protected function migratePushTemplates(): void
    {
        $pushTemplates = Notification::where('type', 'like', 'push:%')->get();

        foreach ($pushTemplates as $push) {
            $userType = preg_replace('/^push:/', 'user:', $push->type);
            $user = Notification::where('type', $userType)->first();

            if ($user && is_null($user->text)) {
                $user->update(['text' => $push->content]);
            }

            $push->delete();
        }
    }

    /**
     * Load notification templates from blade files
     */
    protected function loadNotificationTemplates(): array
    {
        $notifications = [];
        $folders = ['admin', 'user', 'common'];

        foreach ($folders as $folder) {
            $folderPath = database_path("data/templates/$folder");

            if (! File::isDirectory($folderPath)) {
                continue;
            }

            $files = File::glob($folderPath.'/*.blade.php');

            foreach ($files as $file) {
                $notification = $this->parseBladeTemplate($file, $folder);

                if ($notification) {
                    // Use type as key to allow overrides by later sources (stubs override workbench)
                    $notifications[$notification['type']] = $notification;
                }
            }
        }

        // Return re-indexed array values
        return array_values($notifications);
    }

    /**
     * Parse a blade template file and extract metadata from JSON file
     */
    protected function parseBladeTemplate(string $filePath, string $folder): ?array
    {
        // Get corresponding JSON metadata file
        $jsonFile = str_replace('.blade.php', '.json', $filePath);

        if (! File::exists($jsonFile)) {
            return null;
        }

        // Read metadata from JSON file
        $metadata = json_decode(file_get_contents($jsonFile), true);

        if (! $metadata || ! isset($metadata['type'])) {
            return null;
        }

        // Read template content from Blade file
        $content = file_get_contents($filePath);
        $content = trim($content);

        // Build notification array
        return [
            'label' => $metadata['label'] ?? ucwords(str_replace('-', ' ', basename($filePath, '.blade.php'))),
            'subject' => $metadata['subject'] ?? '',
            'type' => $metadata['type'],
            'is_default' => true,
            'content' => $content,
            'text' => $metadata['text'] ?? null,
        ];
    }
}
