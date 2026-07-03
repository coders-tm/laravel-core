<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Import;
use Coderstm\Models\Notification as Template;

class ImportCompletedNotification extends BaseNotification
{
    public Import $import;

    public $subject;

    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Import $import)
    {
        $this->import = $import;

        $template = Template::default('admin:import-completed');

        // Render using NotificationTemplateRenderer
        $rendered = $template->render($this->import->getShortCodes());

        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
