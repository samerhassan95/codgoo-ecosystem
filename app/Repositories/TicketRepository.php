<?php

namespace App\Repositories;

use App\Http\Requests\TicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Repositories\Common\CommonRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ImageService;

class TicketRepository extends CommonRepository implements TicketRepositoryInterface
{

    protected const REQUEST = TicketRequest::class;
    protected const RESOURCE = TicketResource::class;

    public function model(): string
    {
        return Ticket::class;
    }

}
