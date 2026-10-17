<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Notification;
use App\Models\VendorCommunication;
use Illuminate\Support\Arr;

class CommunicationService
{
    public function __construct() {}

    public function send(
        int $vendorId,
        string $direction,
        string $channel,
        string $subject,
        string $message,
        array $data = []
    ): VendorCommunication {
        $comm = VendorCommunication::create([
            'vendor_id' => $vendorId,
            'subject' => $subject,
            'message' => $message,
            'direction' => $direction,
            'channel' => $channel,
            'sender_id' => auth()->id(),
            'sender_name' => auth()->user()?->name ?? 'System',
            'recipient_name' => $data['recipient_name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'attachments' => $data['attachments'] ?? null,
        ]);

        $this->notifyVendor($vendorId, $subject, $message);
        $this->audit($comm, 'sent', ['channel' => $channel]);

        return $comm;
    }

    public function logInbound(
        int $vendorId,
        string $channel,
        string $subject,
        string $message,
        array $data = []
    ): VendorCommunication {
        $comm = VendorCommunication::create([
            'vendor_id' => $vendorId,
            'subject' => $subject,
            'message' => $message,
            'direction' => 'inbound',
            'channel' => $channel,
            'sender_name' => $data['sender_name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
        ]);

        $this->audit($comm, 'received');

        return $comm;
    }

    public function attachReply(int $messageId, string $replyMessage, array $data = []): VendorCommunication
    {
        $original = VendorCommunication::findOrFail($messageId);

        $reply = VendorCommunication::create([
            'vendor_id' => $original->vendor_id,
            'subject' => "Re: {$original->subject}",
            'message' => $replyMessage,
            'direction' => 'outbound',
            'channel' => $original->channel,
            'sender_id' => auth()->id(),
            'sender_name' => auth()->user()?->name ?? 'System',
            'recipient_name' => $data['recipient_name'] ?? $original->sender_name,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
        ]);

        $this->audit($reply, 'reply_sent');

        return $reply;
    }

    public function getConversation(int $vendorId, ?string $entityType = null, ?int $entityId = null): array
    {
        $query = VendorCommunication::where('vendor_id', $vendorId)->orderByDesc('created_at');

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query->limit(200)->get()->all();
    }

    public function trackResponse(int $vendorId, array $data = []): array
    {
        $recent = $this->getConversation($vendorId);

        $outbound = Arr::where($recent, fn ($c) => $c['direction'] === 'outbound');
        $inbound = Arr::where($recent, fn ($c) => $c['direction'] === 'inbound');

        $totalSent = count($outbound);
        $totalReceived = count($inbound);

        // Calculate average response time for recent outbound messages
        $avgResponseTime = 0;
        if ($totalSent > 0 && $totalReceived > 0) {
            $sentDates = array_map(fn ($c) => strtotime($c['created_at']), $outbound);
            $recvDates = array_map(fn ($c) => strtotime($c['created_at']), $inbound);

            $totalDiff = 0;
            $count = min(count($sentDates), count($recvDates));
            for ($i = 0; $i < $count; $i++) {
                $totalDiff += abs($recvDates[$i] - $sentDates[$i]);
            }
            $avgResponseTime = $count > 0 ? round($totalDiff / $count / 3600, 2) : 0;
        }

        return [
            'vendor_id' => $vendorId,
            'total_messages' => $totalSent + $totalReceived,
            'outbound_count' => $totalSent,
            'inbound_count' => $totalReceived,
            'avg_response_time_hours' => $avgResponseTime,
        ];
    }

    public function sendAttachment(int $messageId, array $files): array
    {
        $original = VendorCommunication::findOrFail($messageId);
        $paths = [];

        foreach ($files as $file) {
            $path = $file->store('communications/attachments', 'public');
            $paths[] = $path;
        }

        $attachments = array_merge($original->attachments ?? [], $paths);

        $original->update(['attachments' => $attachments]);

        return $paths;
    }

    private function notifyVendor(int $vendorId, string $subject, string $message): void
    {
        // Create notification for vendor admin users if any
        // This is a placeholder for email/notification integration
    }

    private function audit(VendorCommunication $comm, string $action, array $meta = []): void
    {
        AuditTrail::create([
            'actor_id' => auth()->id(),
            'action' => "communication.{$action}",
            'entity_type' => VendorCommunication::class,
            'entity_id' => $comm->id,
            'metadata' => array_merge([
                'subject' => $comm->subject,
                'direction' => $comm->direction,
            ], $meta),
        ]);
    }
}
