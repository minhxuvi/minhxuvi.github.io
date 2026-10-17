<?php

namespace App\Exceptions;

class GitHubFailure extends \RuntimeException
{
    public function __construct(public int $httpStatus = 503, string $message = 'GitHub chưa phản hồi. Nội dung đang soạn được giữ lại; hãy thử lại.')
    {
        parent::__construct($message);
    }
}
