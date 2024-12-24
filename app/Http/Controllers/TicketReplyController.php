<?php
namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\TicketReply;
use App\Repositories\TicketReplyRepositoryInterface;
use App\Services\ImageService;
use Illuminate\Http\Request;

class TicketReplyController extends BaseController
{
    private $repository;

    public function __construct(TicketReplyRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

}
