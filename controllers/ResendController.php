<?php

declare(strict_types=1);

namespace Osmium\Services\Resend\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\Resend\Models\ResendConfig;

/**
 * Resend settings controller - full-page form POST/redirect, same shape as
 * TurnstileController.
 *
 * Routes:
 *   - index() → /admin/settings/resend/  (GET shows the form, POST saves it)
 */
class ResendController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/resend.json.php';

    public function index(): void
    {
        $isPost = $this->isPost();
        if ($isPost) $this->handleSubmit();

        $this->data['admin']['config']['resend'] = (array) ResendConfig::get();
        $this->data['admin']['settingsSaved'] = $_SESSION['resend_settings_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['resend_settings_error'] ?? false;
        unset($_SESSION['resend_settings_saved'], $_SESSION['resend_settings_error']);

        $this->setView('resend/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['resend_settings_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/resend/');
        }

        $postedApiKey = \trim($_POST['api_key'] ?? '');
        $apiKey = $postedApiKey === '' ? (string) (ResendConfig::get()->apiKey ?? '') : $postedApiKey; // Blank keeps the stored secret

        $this->saveConfig($apiKey);

        $this->admin->model->changelog->log(
            description: 'Updated Resend settings',
            recordType: 'settings',
        );

        ResendConfig::clearCache();

        $_SESSION['resend_settings_saved'] = true;
        $this->redirect('settings/resend/');
    }

    private function saveConfig(string $apiKey): void
    {
        $configExists = \file_exists(self::CONFIG_FILE_PATH);
        if (!$configExists) $this->ensureConfigDirectoryExists();

        $newJson = \json_encode(
            value: ['resend' => ['apiKey' => $apiKey]],
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, "<?php exit(); ?>\n" . $newJson . "\n");
    }

    private function ensureConfigDirectoryExists(): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $alreadyExists = \is_dir($dir);
        if (!$alreadyExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
