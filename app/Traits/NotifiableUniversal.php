<?php

namespace App\Traits;

use App\Models\NotificationUniversal;
use App\Services\NotificationUniversalService;

trait NotifiableUniversal
{
    /**
     * Get all notifications for this model
     */
    public function notifications()
    {
        return $this->morphMany(NotificationUniversal::class, 'reference');
    }

    /**
     * Send notification to user
     */
    public function sendNotification($userId, $data)
    {
        return app(NotificationUniversalService::class)->send($userId, array_merge([
            'reference_type' => get_class($this),
            'reference_id' => $this->id,
            'reference_code' => $this->getNotificationReferenceCode(),
            'module' => $this->getNotificationModule(),
        ], $data));
    }

    /**
     * Send notification to multiple users
     */
    public function sendNotificationToMany($userIds, $data)
    {
        return app(NotificationUniversalService::class)->sendToMany($userIds, array_merge([
            'reference_type' => get_class($this),
            'reference_id' => $this->id,
            'reference_code' => $this->getNotificationReferenceCode(),
            'module' => $this->getNotificationModule(),
        ], $data));
    }

    /**
     * Get notification reference code - override di model
     */
    public function getNotificationReferenceCode()
    {
        return property_exists($this, 'notificationReferenceCode') 
            ? $this->{$this->notificationReferenceCode} 
            : $this->id;
    }

    /**
     * Get notification module - override di model
     */
    public function getNotificationModule()
    {
        return property_exists($this, 'notificationModule') 
            ? $this->notificationModule 
            : strtolower(class_basename($this));
    }
}