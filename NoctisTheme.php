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
    public const CUSTOM_VERSION = '0.2.0';
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
            'url'  => self::AUTHOR_WEBSITE,
            'text' => 'Noctis theme by ' . self::CUSTOM_AUTHOR,
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
            // Avatar is purely cosmetic — fail silently
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
            'if(!el)return;' .
            'var img=document.createElement("img");' .
            'img.src=' . $safeUrl . ';' .
            'img.className="mn-user-avatar";' .
            'img.alt="";' .
            'el.prepend(img);' .
            '})();</script>';

        // Ambient aurora background effect
        $footer .= '<script>(function(){' .
            'var a=document.createElement("div");a.className="mn-aurora";' .
            'a.setAttribute("aria-hidden","true");' .
            'for(var i=1;i<=3;i++){var b=document.createElement("div");' .
            'b.className="mn-aurora-blob mn-aurora-blob--"+i;a.appendChild(b);}' .
            'document.body.prepend(a);' .
            '})();</script>';

        // Fix Leaflet maps: force recalculation after all CSS is loaded
        $footer .= '<script>(function(){' .
            'window.addEventListener("load",function(){' .
                'document.querySelectorAll(".leaflet-container").forEach(function(el){' .
                    'var map=el._leaflet_map||el._leaflet;' .
                    'if(map&&map.invalidateSize)map.invalidateSize();' .
                '});' .
            '});' .
            '})();</script>';

        // Fix orphaned modal backdrops that block page interaction
        $footer .= '<script>(function(){' .
            'function isModalOpen(){return document.querySelector(".modal.show, .modal.in, .modal[style*=\"display: block\"]");}' .
            'function cleanBackdrops(){' .
                'if(!isModalOpen()){' .
                    'document.querySelectorAll(".modal-backdrop").forEach(function(el){el.remove();});' .
                    'document.body.classList.remove("modal-open");' .
                    'document.body.style.removeProperty("overflow");' .
                    'document.body.style.removeProperty("padding-right");' .
                '}' .
            '}' .
            'cleanBackdrops();' .
            'document.addEventListener("DOMContentLoaded",cleanBackdrops);' .
            'window.addEventListener("load",cleanBackdrops);' .
            'document.addEventListener("hidden.bs.modal",function(){setTimeout(cleanBackdrops,100);});' .
            'new MutationObserver(function(m){' .
                'm.forEach(function(mut){' .
                    'mut.addedNodes.forEach(function(n){' .
                        'if(n.classList&&n.classList.contains("modal-backdrop")&&!isModalOpen()){' .
                            'setTimeout(function(){if(!isModalOpen())n.remove();},50);' .
                        '}' .
                    '});' .
                '});' .
            '}).observe(document.body,{childList:true});' .
            'setInterval(cleanBackdrops,1000);' .
            '})();</script>';

        // Mobile-only Bootstrap collapse menu for MinimalTheme header structure
        $footer .= <<<'HTML'
<script>
(function () {
    function initMobileBootstrapMenu() {
        if (!window.matchMedia || !window.matchMedia('(max-width: 767.98px)').matches) {
            return;
        }

        var headerRow = document.querySelector('.wt-header-wrapper .wt-header-container > .row.wt-header-content');
        var headerWrapper = document.querySelector('.wt-header-wrapper');
        var primaryNav = document.querySelector('.wt-header-wrapper .wt-primary-navigation');
        var secondaryNav = document.querySelector('.wt-header-wrapper .wt-secondary-navigation');

        if (!headerRow || !headerWrapper || !primaryNav || !secondaryNav || document.getElementById('mnMobileMenuToggles')) {
            return;
        }

        primaryNav.id = primaryNav.id || 'mnPrimaryMenuMobile';
        secondaryNav.id = secondaryNav.id || 'mnSecondaryMenuMobile';

        primaryNav.classList.add('collapse', 'mn-mobile-collapse');
        secondaryNav.classList.add('collapse', 'mn-mobile-collapse');
        primaryNav.classList.remove('show');
        secondaryNav.classList.remove('show');
        headerWrapper.classList.add('mn-mobile-menu-ready');

        var toggleBar = document.createElement('div');
        toggleBar.id = 'mnMobileMenuToggles';
        toggleBar.className = 'mn-mobile-nav-togglebar mn-mobile-row-toggles d-md-none';
        toggleBar.innerHTML =
            '<button id="mnMobilePrimaryBtn" class="navbar-toggler" type="button" aria-controls="' + primaryNav.id + '" aria-expanded="false" aria-label="Toggle primary navigation">' +
                '<i class="fa-solid fa-bars" aria-hidden="true"></i>' +
            '</button>' +
            '<button id="mnMobileSecondaryBtn" class="navbar-toggler" type="button" aria-controls="' + secondaryNav.id + '" aria-expanded="false" aria-label="Toggle user navigation">' +
                '<i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>' +
            '</button>' +
            '<button id="mnMobileSearchBtn" class="navbar-toggler" type="button" aria-expanded="false" aria-label="Toggle search">' +
                '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>' +
            '</button>';

        var siteTitle = headerRow.querySelector('.wt-site-title');
        var headerSearch = headerRow.querySelector('.wt-header-search');

        headerRow.classList.add('mn-mobile-header-stack');
        if (siteTitle) {
            siteTitle.classList.add('mn-mobile-row-title');
        }
        if (headerSearch) {
            headerSearch.id = headerSearch.id || 'mnMobileSearchRow';
            headerSearch.classList.add('mn-mobile-row-search');
        }

        if (siteTitle) {
            headerRow.appendChild(siteTitle);
        }
        if (headerSearch) {
            headerRow.appendChild(headerSearch);
        }
        headerRow.appendChild(toggleBar);

        var primaryBtn = document.getElementById('mnMobilePrimaryBtn');
        var secondaryBtn = document.getElementById('mnMobileSecondaryBtn');
        var searchBtn = document.getElementById('mnMobileSearchBtn');
        var hasBootstrapCollapse = typeof bootstrap !== 'undefined' && !!bootstrap.Collapse;
        var primaryCollapse = hasBootstrapCollapse ? bootstrap.Collapse.getOrCreateInstance(primaryNav, {toggle: false}) : null;
        var secondaryCollapse = hasBootstrapCollapse ? bootstrap.Collapse.getOrCreateInstance(secondaryNav, {toggle: false}) : null;

        // Wire aria-controls on search button now that the search element id is known.
        if (searchBtn && headerSearch && headerSearch.id) {
            searchBtn.setAttribute('aria-controls', headerSearch.id);
        }

        function setExpanded(button, expanded) {
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        function openPanel(nav, collapse) {
            if (hasBootstrapCollapse) {
                collapse.show();
            } else {
                nav.classList.add('show');
            }
        }

        function hidePanel(nav, collapse) {
            if (hasBootstrapCollapse) {
                collapse.hide();
            } else {
                nav.classList.remove('show');
            }
        }

        function isOpen(nav) {
            return nav.classList.contains('show');
        }

        function showSearch() {
            if (headerSearch) { headerSearch.classList.add('show'); }
        }
        function hideSearch() {
            if (headerSearch) { headerSearch.classList.remove('show'); }
        }
        function isSearchOpen() {
            return headerSearch ? headerSearch.classList.contains('show') : false;
        }

        primaryNav.addEventListener('shown.bs.collapse', function () { setExpanded(primaryBtn, true); });
        primaryNav.addEventListener('hidden.bs.collapse', function () { setExpanded(primaryBtn, false); });
        secondaryNav.addEventListener('shown.bs.collapse', function () { setExpanded(secondaryBtn, true); });
        secondaryNav.addEventListener('hidden.bs.collapse', function () { setExpanded(secondaryBtn, false); });

        if (!hasBootstrapCollapse) {
            setExpanded(primaryBtn, false);
            setExpanded(secondaryBtn, false);
        }

        primaryBtn.addEventListener('click', function () {
            if (isOpen(primaryNav)) {
                hidePanel(primaryNav, primaryCollapse);
                setExpanded(primaryBtn, false);
                return;
            }
            hidePanel(secondaryNav, secondaryCollapse);
            hideSearch();
            if (searchBtn) { setExpanded(searchBtn, false); }
            openPanel(primaryNav, primaryCollapse);
            if (!hasBootstrapCollapse) {
                setExpanded(primaryBtn, true);
                setExpanded(secondaryBtn, false);
            }
        });

        secondaryBtn.addEventListener('click', function () {
            if (isOpen(secondaryNav)) {
                hidePanel(secondaryNav, secondaryCollapse);
                setExpanded(secondaryBtn, false);
                return;
            }
            hidePanel(primaryNav, primaryCollapse);
            hideSearch();
            if (searchBtn) { setExpanded(searchBtn, false); }
            openPanel(secondaryNav, secondaryCollapse);
            if (!hasBootstrapCollapse) {
                setExpanded(primaryBtn, false);
                setExpanded(secondaryBtn, true);
            }
        });

        if (searchBtn) {
            searchBtn.addEventListener('click', function () {
                if (isSearchOpen()) {
                    hideSearch();
                    setExpanded(searchBtn, false);
                    return;
                }
                hidePanel(primaryNav, primaryCollapse);
                hidePanel(secondaryNav, secondaryCollapse);
                if (!hasBootstrapCollapse) {
                    setExpanded(primaryBtn, false);
                    setExpanded(secondaryBtn, false);
                }
                showSearch();
                setExpanded(searchBtn, true);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', initMobileBootstrapMenu);
    window.addEventListener('load', initMobileBootstrapMenu);
})();
</script>
HTML;

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
