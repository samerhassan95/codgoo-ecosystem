<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon; 
class Milestone extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
    
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function setEndDateAttribute($value)
    {
        if ($this->attributes['start_date'] && $this->attributes['period']) {
            $this->attributes['end_date'] = Carbon::parse($this->attributes['start_date'])
                ->addDays($this->attributes['period'])->toDateString();
        }
    }

    protected static function booted()
    {
        static::updated(function ($milestone) {
            if ($milestone->isDirty('status') && $milestone->status === 'completed') {
                $milestone->project->updateProjectStatusIfNeeded();
            }
    
            if ($milestone->status === 'completed') {
                $invoice = \App\Models\Invoice::create([
                    'milestone_id' => $milestone->id,
                    'project_id' => $milestone->project_id,
                    'status' => 'unpaid',
                    'due_date' => now()->addDays(30),
                    'amount' => $milestone->cost,
                ]);
    
                $milestone->sendInvoiceNotification($invoice);
            }
        });
    }
    
   
    public function sendInvoiceNotification($invoice)
    {
        $client = $this->project->client;
    
        if (!$client || !$client->device_token) {
            \Log::warning('Client not found or has no device token for invoice notification.', [
                'milestone_id' => $this->id,
                'project_id' => $this->project_id,
                'client_id' => $client ? $client->id : null
            ]);
            return;
        }
    
        $template = \App\Models\NotificationTemplate::where('type', 'invoice_created')->first();
        if (!$template) {
            \Log::error('Notification template "invoice_created" not found.');
            return;
        }
    
        $title = $template->title;
        $message = str_replace(
            ['{invoice_id}', '{amount}', '{due_date}'],
            ['INV-' . $invoice->id, $invoice->amount, $invoice->due_date->format('d-m-Y')],
            $template->message
        );
    
        try {

            $dataPayload = [
                'invoice_id' => $invoice->id,
                'notification_type' => 'invoice_created',
            ];
            app(\App\Services\FirebaseService::class)->sendNotification($client->device_token, $title, $message, $dataPayload);
    
            app(\App\Repositories\NotificationRepository::class)->createNotification($client, $title, $message, $client->device_token);
    
            \Log::info('Invoice notification sent successfully.', ['client_id' => $client->id, 'invoice_id' => $invoice->id]);
        } catch (\Exception $e) {
            \Log::error('Error sending invoice notification: ' . $e->getMessage());
        }
    }
    
}
