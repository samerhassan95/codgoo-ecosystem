<?php

namespace App\Repositories;

use App\Repositories\Common\CommonRepositoryInterface;

interface HolidayRequestTypeRepositoryInterface extends CommonRepositoryInterface
{
   public function getVisible();

}
