<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Location: ' . BASE_URL . '/auth/oauth/supabase.php?provider=apple');
exit;
