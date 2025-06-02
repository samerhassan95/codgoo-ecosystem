<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Client;
use App\Repositories\AddonRepository;
use App\Repositories\AddonRepositoryInterface;
use App\Repositories\EarlyLeaveRequestRepository;
use App\Repositories\EarlyLeaveRequestRepositoryInterface;
use App\Repositories\Employee\EmployeeRepository;
use App\Repositories\Employee\EmployeeRepositoryInterface;
use App\Repositories\MoneyRequestRepository;
use App\Repositories\MoneyRequestRepositoryInterface;
use App\Repositories\PaperRequestRepository;
use App\Repositories\PaperRequestRepositoryInterface;
use App\Repositories\ProductAddonRepository;
use App\Repositories\ProductAddonRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProjectAddonRepository;
use App\Repositories\ProjectAddonRepositoryInterface;
use App\Repositories\RemoteWorkRequestRepository;
use App\Repositories\RemoteWorkRequestRepositoryInterface;
use App\Repositories\ScreenReviewRepository;
use App\Repositories\ScreenReviewRepositoryInterface;
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
use App\Repositories\HolidayRequestRepositoryInterface;
use App\Repositories\HolidayRequestRepository;
use App\Repositories\ExtendTaskTimeRequestRepository;
use App\Repositories\ExtendTaskTimeRequestRepositoryInterface;
use App\Repositories\TaskAssignmentRepository;
use App\Repositories\TaskAssignmentRepositoryInterface;
use App\Repositories\ProjectGeneralNoteRepository;
use App\Repositories\ProjectGeneralNoteRepositoryInterface;
use app\Repositories\AddressRepository;
use app\Repositories\AddressRepositoryInterface;
use App\Repositories\ScreenRepository;
use App\Repositories\ScreenRepositoryInterface;
use App\Repositories\RequestedApiRepository;
use App\Repositories\RequestedApiRepositoryInterface;
use App\Repositories\ImplementedApiRepository;
use App\Repositories\ImplementedApiRepositoryInterface;
use App\Repositories\ImplementedApiReviewRepository;
use App\Repositories\ImplementedApiReviewRepositoryInterface;
use App\Repositories\AchievementRepository;
use App\Repositories\AchievementRepositoryInterface;
use App\Repositories\AttendanceRepository;
use App\Repositories\AttendanceRepositoryInterface;


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
        $this->app->bind(HolidayRequestRepositoryInterface::class, HolidayRequestRepository::class);
        $this->app->bind(RemoteWorkRequestRepositoryInterface::class, RemoteWorkRequestRepository::class);
        $this->app->bind(EarlyLeaveRequestRepositoryInterface::class, EarlyLeaveRequestRepository::class);
        $this->app->bind(PaperRequestRepositoryInterface::class, PaperRequestRepository::class);
        $this->app->bind(MoneyRequestRepositoryInterface::class, MoneyRequestRepository::class);
        $this->app->bind(ExtendTaskTimeRequestRepositoryInterface::class, ExtendTaskTimeRequestRepository::class);
        $this->app->bind(TaskAssignmentRepositoryInterface::class, TaskAssignmentRepository::class);
        $this->app->bind(ProjectGeneralNoteRepositoryInterface::class, ProjectGeneralNoteRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
        $this->app->bind(ScreenRepositoryInterface::class, ScreenRepository::class);
        $this->app->bind(ScreenReviewRepositoryInterface::class, ScreenReviewRepository::Repository::class);
        $this->app->bind(RequestedApiRepositoryInterface::class, RequestedApiRepository::class);
        $this->app->bind(ImplementedApiRepositoryInterface::class, ImplementedApiRepository::class);
        $this->app->bind(ImplementedApiReviewRepositoryInterface::class, ImplementedApiReviewRepository::class);
        $this->app->bind(AchievementRepositoryInterface::class, AchievementRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      }
}
