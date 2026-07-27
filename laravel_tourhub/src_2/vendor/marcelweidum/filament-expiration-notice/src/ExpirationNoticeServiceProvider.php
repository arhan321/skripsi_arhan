<?php

declare(strict_types=1);

namespace MarcelWeidum\ExpirationNoticePlugin;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\View\View;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class ExpirationNoticeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-expiration-notice';

    public static string $viewNamespace = 'filament-expiration-notice';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('marcelweidum/filament-expiration-notice');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(self::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): View => view('filament-expiration-notice::session-expired-modal'),
        );
    }

    protected function getAssetPackageName(): string
    {
        return 'marcelweidum/filament-expiration-notice';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Js::make('filament-expiration-notice-scripts', __DIR__.'/../resources/dist/filament-expiration-notice.js'),
        ];
    }
}
