<?php

namespace Coderstm\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use SerializesModels;

    public function build()
    {
        return $this->view('coderstm::emails.test');
    }
}
