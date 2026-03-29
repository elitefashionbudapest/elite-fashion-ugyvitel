<?php if ($success = flash('success')): ?>
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2" id="flash-success">
        <i class="fa-solid fa-circle-check text-base"></i>
        <span><?= e($success) ?></span>
        <?php if ($link = flash('success_link')): ?>
            <a href="<?= e($link['url']) ?>" class="underline font-bold hover:text-green-900 ml-1"><?= e($link['text']) ?></a>
        <?php endif; ?>
        <button onclick="this.parentElement.remove()" class="ml-auto text-green-500 hover:text-green-700">
            <i class="fa-solid fa-xmark text-base"></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($error = flash('error')): ?>
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2" id="flash-error">
        <i class="fa-solid fa-circle-exclamation text-base"></i>
        <span><?= e($error) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-red-500 hover:text-red-700">
            <i class="fa-solid fa-xmark text-base"></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($warning = flash('warning')): ?>
    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl text-sm flex items-center gap-2" id="flash-warning">
        <i class="fa-solid fa-triangle-exclamation text-base"></i>
        <span><?= e($warning) ?></span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-yellow-500 hover:text-yellow-700">
            <i class="fa-solid fa-xmark text-base"></i>
        </button>
    </div>
<?php endif; ?>
