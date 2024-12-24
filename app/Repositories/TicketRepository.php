<?php

namespace App\Repositories;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Models\TicketReply;
use App\Models\TicketReplyReply;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ImageService;

class TicketReplyRepository extends CommonRepository implements TicketReplyRepositoryInterface
{

    protected const REQUEST = TicketReplyRequest::class;
    protected const RESOURCE = TicketReplyResource::class;

    public function model(): string
    {
        return TicketReply::class;
    }

}
