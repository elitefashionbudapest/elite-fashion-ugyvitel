<?php $notifications = $data['notifications'] ?? []; ?>

<div class="max-w-2xl">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-heading font-bold text-gray-900">Értesítések</h3>
            <span class="text-xs text-gray-400"><?= count($notifications) ?> értesítés</span>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center text-gray-400">
                <i class="fa-solid fa-bell-slash text-4xl mb-2"></i>
                <p>Nincs értesítés</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($notifications as $n): ?>
                <div class="px-6 py-4 flex items-start gap-3 <?= $n['is_read'] ? 'opacity-60' : '' ?>">
                    <i class="fa-solid <?= match($n['type']) {
                        'chat' => 'fa-comments text-blue-500',
                        'schedule' => 'fa-calendar-days text-purple-500',
                        'vacation' => 'fa-umbrella-beach text-orange-500',
                        'system' => 'fa-circle-info text-gray-500',
                        default => 'fa-bell text-primary',
                    } ?> text-lg mt-0.5"></i>
                    <div class="flex-grow">
                        <p class="text-sm font-medium text-gray-900"><?= e($n['title']) ?></p>
                        <?php if ($n['message']): ?>
                            <p class="text-xs text-gray-500 mt-0.5"><?= e($n['message']) ?></p>
                        <?php endif; ?>
                        <p class="text-[10px] text-gray-400 mt-1"><?= date('Y.m.d H:i', strtotime($n['created_at'])) ?></p>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <span class="h-2 w-2 bg-primary rounded-full mt-2 flex-shrink-0"></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
