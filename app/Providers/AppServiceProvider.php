<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Client;
use App\Repositories\AddonRepository;
use App\Repositories\AddonRepositoryInterface;
use App\Repositories\Employee\EmployeeRepository;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use App\Repositories\ProductAddonRepository;
use App\Repositories\ProductAddonRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProjectAddonRepository;
use App\Repositories\ProjectAddonRepositoryInterface;
use App\Repositories\SkillRepository;
use App\Repositories\SkillRepositoryInterface;
use App\Repositories\TopicGalleryRepository;
use App\Repositories\TopicGalleryRepositoryInterface;
use App\Repositories\TopicRepository;
use App\Repositories\TopicRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Admin\AdminRepositoryInterface;
use App\Repositories\Admin\AdminRepository;
use App\Repositories\Client\ClientRepositoryInterface;
use App\Repositories\Client\ClientRepository;
use App\Repositories\ProductMediaRepositoryInterface;
use App\Repositories\ProductMediaRepository;
use App\Repositories\ProjectRepositoryInterface;
use App\Repositories\ProjectRepository;
use App\Repositories\MilestoneRepositoryInterface;
use App\Repositories\MilestoneRepository;
use App\Repositories\TaskRepositoryInterface;
use App\Repositories\TaskRepository;
use App\Repositories\SliderRepository;
use App\Repositories\SliderRepositoryInterface;
use App\Repositories\InvoiceRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\MeetingRepositoryInterface;
use App\Repositories\MeetingRepository;
use App\Repositories\TicketRepository;
use App\Repositories\TicketRepositoryInterface;
use App\Repositories\TicketReplyRepository;
use App\Repositories\TicketReplyRepositoryInterface;
use App\Repositories\DepartmentRepositoryInterface;
use App\Repositories\DepartmentRepository;
use App\Repositories\GalleryRepositoryInterface;
use App\Repositories\GalleryRepository;
use App\Repositories\CategoryRepositoryInterface;
use App\Repositories\CategoryRepository;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductMediaRepositoryInterface::class, ProductMediaRepository::class);
        $this->app->bind(AddonRepositoryInterface::class, AddonRepository::class);
        $this->app->bind(ProductAddonRepositoryInterface::class, ProductAddonRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, ProjectRepository::class);
        $this->app->bind(MilestoneRepositoryInterface::class, MilestoneRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(ProjectAddonRepositoryInterface::class, ProjectAddonRepository::class);
        $this->app->bind(SliderRepositoryInterface::class, SliderRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
        $this->app->bind(TopicRepositoryInterface::class, TopicRepository::class);
        $this->app->bind(TopicGalleryRepositoryInterface::class, TopicGalleryRepository::class);
        $this->app->bind(TicketReplyRepositoryInterface::class, TicketReplyRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(MeetingRepositoryInterface::class, MeetingRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, SkillRepository::class);
        $this->app->bind(GalleryRepositoryInterface::class, GalleryRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      }
}
