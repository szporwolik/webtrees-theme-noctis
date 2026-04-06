<?php

declare(strict_types=1);

namespace SzymonPorwolik\Webtrees\Module\NoctisTheme;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\MinimalTheme;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleFooterInterface;
use Fisharebest\Webtrees\Module\ModuleFooterTrait;
use Fisharebest\Webtrees\Module\ModuleThemeInterface;
use Fisharebest\Webtrees\Module\ModuleThemeTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Session;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ServerRequestInterface;

class NoctisTheme extends MinimalTheme implements ModuleThemeInterface, ModuleCustomInterface, ModuleFooterInterface
{
    use ModuleThemeTrait;
    use ModuleCustomTrait;
    use ModuleFooterTrait;

    public const CUSTOM_AUTHOR = 'Szymon Porwolik';
    public const CUSTOM_VERSION = '0.8.0';
    public const AUTHOR_WEBSITE = 'https://szymon.porwolik.com';
    public const CUSTOM_SUPPORT_URL = 'https://github.com/szporwolik/webtrees-theme-noctis';
    public const CUSTOM_LATEST_VERSION_URL = 'https://raw.githubusercontent.com/szporwolik/webtrees-theme-noctis/main/latest-version.txt';

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     */
    public function title(): string
    {
        return I18N::translate('Noctis');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::description()
     */
    public function description(): string
    {
        return I18N::translate('A modern dark theme for webtrees.');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_SUPPORT_URL;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleLatestVersionUrl()
     */
    public function customModuleLatestVersionUrl(): string
    {
        return self::CUSTOM_LATEST_VERSION_URL;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::boot()
     */
    public function boot(): void
    {
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
        View::registerCustomView('::individual-page-images', $this->name() . '::individual-page-images');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\AbstractModule::stylesheets()
     */
    public function stylesheets(): array
    {
        return array_merge(parent::stylesheets(), [
            $this->assetUrl('css/noctis.css'),
        ]);
    }

    /**
     * A footer, to be added at the bottom of every page.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function getFooter(ServerRequestInterface $request): string
    {
        if (Session::get('theme') !== $this->name()) {
            return '';
        }

        $footer = view($this->name() . '::theme/footer-credits', [
            'url'        => self::AUTHOR_WEBSITE,
            'github_url' => self::CUSTOM_SUPPORT_URL,
            'version'    => 'v' . self::CUSTOM_VERSION,
        ]);

        $avatarUrl = '';

        try {
            $tree = $request->getAttribute('tree');
            $user = Auth::user();

            if ($tree instanceof Tree && $user->id() > 0) {
                $gedcomId = $tree->getUserPreference($user, 'gedcomid');

                if ($gedcomId !== '') {
                    $individual = Registry::individualFactory()->make($gedcomId, $tree);

                    if ($individual !== null) {
                        $mediaFile = $individual->findHighlightedMediaFile();

                        if ($mediaFile !== null) {
                            $avatarUrl = $mediaFile->imageUrl(64, 64, 'crop');
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            if (filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOL)) {
                error_log('[NoctisTheme] Failed to load avatar image: ' . $e->getMessage());
            }
        }

        $placeholder = 'data:image/svg+xml,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">' .
            '<rect width="32" height="32" rx="16" fill="#1a2035"/>' .
            '<circle cx="16" cy="13" r="5.5" fill="#64748b"/>' .
            '<ellipse cx="16" cy="28" rx="10" ry="8" fill="#64748b"/>' .
            '</svg>'
        );

        $imgSrc = $avatarUrl !== '' ? $avatarUrl : $placeholder;
        $safeUrl = json_encode($imgSrc, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);

        $footer .= '<script>(function(){' .
            'var el=document.querySelector(".menu-mymenu > .nav-link");' .
            'if(!el||el.querySelector("img.mn-user-avatar"))return;' .
            'var img=document.createElement("img");' .
            'img.src=' . $safeUrl . ';' .
            'img.className="mn-user-avatar";' .
            'img.alt="";' .
            'img.loading="lazy";' .
            'el.prepend(img);' .
            '})();</script>';

        return $footer;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleThemeInterface::parameter()
     */
    public function parameter($parameter_name)
    {
        $parameters = [
            'chart-background-f'             => '3b2a4a',
            'chart-background-m'             => '1a3a5c',
            'chart-background-u'             => '2a2a3a',
            'chart-box-x'                    => 260,
            'chart-box-y'                    => 85,
            'chart-font-color'               => 'e0e0e8',
            'chart-spacing-x'               => 5,
            'chart-spacing-y'               => 10,
            'compact-chart-box-x'            => 240,
            'compact-chart-box-y'            => 50,
            'distribution-chart-high-values' => '7c6ef0',
            'distribution-chart-low-values'  => '4a3f8a',
            'distribution-chart-no-values'   => '2a2a3a',
        ];

        return $parameters[$parameter_name] ?? parent::parameter($parameter_name);
    }
}
