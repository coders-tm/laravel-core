<?php

namespace Coderstm\Notifications;

use Coderstm\Models\Import;
use Coderstm\Models\Notification as Template;

class ImportCompletedNotification extends BaseNotification
{
    public Import $import;

    public $subject;

    public $message;

    public function __construct(Import $import)
    {
        $this->import = $import;
        $template = Template::default('admin:import-completed');
        $rendered = $template->render($this->import->getShortCodes());
        parent::__construct($rendered['subject'], $rendered['content']);
    }
}
