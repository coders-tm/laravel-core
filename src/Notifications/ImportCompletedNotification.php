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
        $shortCodes = $this->import->getShortCodes();

        $subject = replace_short_code($template->subject, $shortCodes);
        $message = replace_short_code($template->content, $shortCodes);

        parent::__construct($subject, $message);
    }
}
