/* global bootstrap */

(function () {
  function setExpanded (button, expanded) {
    if (button) {
      button.setAttribute('aria-expanded', expanded ? 'true' : 'false')
    }
  }

  function createIcon (className) {
    var icon = document.createElement('i')
    icon.className = className
    icon.setAttribute('aria-hidden', 'true')
    return icon
  }

  function copyMenuItemContent (source, target) {
    var visual = source.querySelector('i, svg, img')
    if (visual) {
      target.appendChild(visual.cloneNode(true))
    }

    var label = (source.textContent || '').replace(/\s+/g, ' ').trim()
    if (label) {
      if (target.childNodes.length > 0) {
        target.appendChild(document.createTextNode(' '))
      }
      target.appendChild(document.createTextNode(label))
    }
  }

  function initHeaderCollapses () {
    var primaryMenu = document.getElementById('primaryMenuM')
    var secondaryMenu = document.getElementById('secondaryMenuM')
    var primaryButton = document.querySelector('[data-bs-target="#primaryMenuM"]')
    var secondaryButton = document.querySelector('[data-bs-target="#secondaryMenuM"]')
    var primarySearchField = primaryMenu ? primaryMenu.querySelector('.wt-header-search-field') : null

    if (!primaryMenu || !secondaryMenu || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
      return
    }

    var primaryCollapse = bootstrap.Collapse.getOrCreateInstance(primaryMenu, { toggle: false })
    var secondaryCollapse = bootstrap.Collapse.getOrCreateInstance(secondaryMenu, { toggle: false })

    primaryMenu.addEventListener('show.bs.collapse', function () {
      secondaryCollapse.hide()
      setExpanded(secondaryButton, false)
    })

    primaryMenu.addEventListener('shown.bs.collapse', function () {
      setExpanded(primaryButton, true)

      if (primarySearchField && window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches) {
        window.setTimeout(function () {
          try {
            primarySearchField.focus({ preventScroll: true })
          } catch (error) {
            primarySearchField.focus()
          }
        }, 140)
      }
    })

    primaryMenu.addEventListener('hidden.bs.collapse', function () {
      setExpanded(primaryButton, false)
    })

    secondaryMenu.addEventListener('show.bs.collapse', function () {
      primaryCollapse.hide()
      setExpanded(primaryButton, false)
    })

    secondaryMenu.addEventListener('shown.bs.collapse', function () {
      setExpanded(secondaryButton, true)
    })

    secondaryMenu.addEventListener('hidden.bs.collapse', function () {
      setExpanded(secondaryButton, false)
    })
  }

  function adjustScrollPadding (mq) {
    var root = document.documentElement
    var headerWrapper = document.querySelector('.wt-header-wrapper')

    if (mq.matches && headerWrapper) {
      root.style.setProperty('scroll-padding-top', (headerWrapper.offsetHeight + 10) + 'px')
    } else {
      root.style.removeProperty('scroll-padding-top')
    }
  }

  function initScrollPadding () {
    if (!window.matchMedia) {
      return
    }

    var mq = window.matchMedia('(min-width: 992px)')
    adjustScrollPadding(mq)

    if (mq.addEventListener) {
      mq.addEventListener('change', adjustScrollPadding)
    } else if (mq.addListener) {
      mq.addListener(adjustScrollPadding)
    }
  }

  function highlightActiveNavItems () {
    var currentUrl = window.location.href

    document.querySelectorAll('.wt-primary-navigation a[href], .wt-secondary-navigation a[href]').forEach(function (link) {
      if (link.href !== currentUrl) {
        return
      }

      link.classList.add('active')

      var navigation = link.closest('.wt-primary-navigation')
      var parentItem = link.closest('li')

      if (navigation && parentItem) {
        parentItem.querySelectorAll('.nav-link').forEach(function (navLink) {
          navLink.classList.add('mn-nav-link-active')
        })
      }
    })
  }

  function initAurora () {
    var mobileMq = window.matchMedia ? window.matchMedia('(max-width: 767.98px)') : null
    var motionMq = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null

    function shouldDisable () {
      return !!((mobileMq && mobileMq.matches) || (motionMq && motionMq.matches))
    }

    function ensureAurora () {
      var existing = document.querySelector('.mn-aurora')

      if (shouldDisable()) {
        if (existing) {
          existing.remove()
        }
        return
      }

      if (existing || !document.body) {
        return
      }

      var aurora = document.createElement('div')
      aurora.className = 'mn-aurora'
      aurora.setAttribute('aria-hidden', 'true')

      for (var i = 1; i <= 3; i++) {
        var blob = document.createElement('div')
        blob.className = 'mn-aurora-blob mn-aurora-blob--' + i
        aurora.appendChild(blob)
      }

      document.body.prepend(aurora)
    }

    ensureAurora()

    if (mobileMq) {
      if (mobileMq.addEventListener) {
        mobileMq.addEventListener('change', ensureAurora)
      } else if (mobileMq.addListener) {
        mobileMq.addListener(ensureAurora)
      }
    }

    if (motionMq) {
      if (motionMq.addEventListener) {
        motionMq.addEventListener('change', ensureAurora)
      } else if (motionMq.addListener) {
        motionMq.addListener(ensureAurora)
      }
    }
  }

  function initLeafletRefresh () {
    window.addEventListener('load', function () {
      document.querySelectorAll('.leaflet-container').forEach(function (element) {
        var map = element._leaflet_map || element._leaflet
        if (map && map.invalidateSize) {
          map.invalidateSize()
        }
      })
    }, { once: true })
  }

  function initModalBackdropCleanup () {
    if (!document.body || document.body.dataset.mnBackdropCleanupInitialized === 'true') {
      return
    }

    document.body.dataset.mnBackdropCleanupInitialized = 'true'

    function isModalOpen () {
      return document.querySelector('.modal.show, .modal.in, .modal[style*="display: block"]')
    }

    function cleanBackdrops () {
      if (!document.body || isModalOpen()) {
        return
      }

      document.querySelectorAll('.modal-backdrop').forEach(function (element) {
        element.remove()
      })

      document.body.classList.remove('modal-open')
      document.body.style.removeProperty('overflow')
      document.body.style.removeProperty('padding-right')
    }

    cleanBackdrops()
    document.addEventListener('hidden.bs.modal', function () {
      window.setTimeout(cleanBackdrops, 100)
    })
    document.addEventListener('shown.bs.modal', cleanBackdrops)

    if ('MutationObserver' in window) {
      new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          mutation.addedNodes.forEach(function (node) {
            if (node.classList && node.classList.contains('modal-backdrop') && !isModalOpen()) {
              window.setTimeout(function () {
                if (!isModalOpen()) {
                  node.remove()
                }
              }, 50)
            }
          })
        })
      }).observe(document.body, { childList: true })
    }
  }

  function initMobileBootstrapMenu () {
    if (!window.matchMedia || !window.matchMedia('(max-width: 767.98px)').matches) {
      return
    }

    var headerRow = document.querySelector('.wt-header-wrapper .wt-header-container > .row.wt-header-content')
    var headerWrapper = document.querySelector('.wt-header-wrapper')
    var primaryNav = document.querySelector('.wt-header-wrapper .wt-primary-navigation')
    var secondaryNav = document.querySelector('.wt-header-wrapper .wt-secondary-navigation')

    if (!headerRow || !headerWrapper || !primaryNav || !secondaryNav || document.getElementById('mnMobileMenuToggles')) {
      return
    }

    primaryNav.id = primaryNav.id || 'mnPrimaryMenuMobile'
    secondaryNav.id = secondaryNav.id || 'mnSecondaryMenuMobile'

    primaryNav.classList.add('collapse', 'mn-mobile-collapse')
    secondaryNav.classList.add('collapse', 'mn-mobile-collapse')
    primaryNav.classList.remove('show')
    secondaryNav.classList.remove('show')
    headerWrapper.classList.add('mn-mobile-menu-ready')

    var toggleBar = document.createElement('div')
    toggleBar.id = 'mnMobileMenuToggles'
    toggleBar.className = 'mn-mobile-nav-togglebar mn-mobile-row-toggles d-md-none'

    var primaryBtn = document.createElement('button')
    primaryBtn.id = 'mnMobilePrimaryBtn'
    primaryBtn.className = 'navbar-toggler'
    primaryBtn.type = 'button'
    primaryBtn.setAttribute('aria-controls', primaryNav.id)
    primaryBtn.setAttribute('aria-expanded', 'false')
    primaryBtn.setAttribute('aria-label', 'Toggle primary navigation')
    primaryBtn.appendChild(createIcon('fa-solid fa-bars'))
    toggleBar.appendChild(primaryBtn)

    var searchBtn = document.createElement('button')
    searchBtn.id = 'mnMobileSearchBtn'
    searchBtn.className = 'navbar-toggler'
    searchBtn.type = 'button'
    searchBtn.setAttribute('aria-expanded', 'false')
    searchBtn.setAttribute('aria-label', 'Toggle search')
    searchBtn.appendChild(createIcon('fa-solid fa-magnifying-glass'))
    toggleBar.appendChild(searchBtn)

    function createMobileUserChip () {
      var userLink = document.querySelector('.wt-secondary-navigation .menu-mymenu > .nav-link, #secondaryMenu .menu-mymenu > .nav-link, #secondaryMenuM .menu-mymenu > .nav-link')
      if (!userLink) {
        return null
      }

      var userName = (userLink.textContent || '').replace(/\s+/g, ' ').trim()
      if (!userName) {
        return null
      }

      var chip = document.createElement('button')
      chip.type = 'button'
      chip.className = 'mn-mobile-user-chip'
      chip.setAttribute('aria-controls', secondaryNav.id)
      chip.setAttribute('aria-expanded', 'false')
      chip.setAttribute('aria-label', userName)

      var avatar = userLink.querySelector('img.mn-user-avatar, img')
      if (avatar && avatar.getAttribute('src')) {
        var avatarImage = document.createElement('img')
        avatarImage.className = 'mn-mobile-user-chip-image'
        avatarImage.src = avatar.getAttribute('src')
        avatarImage.alt = ''
        avatarImage.loading = 'lazy'
        chip.appendChild(avatarImage)
      } else {
        var icon = document.createElement('span')
        icon.className = 'mn-mobile-user-chip-icon'
        icon.appendChild(createIcon('fa-regular fa-user'))
        chip.appendChild(icon)
      }

      var name = document.createElement('span')
      name.className = 'mn-mobile-user-chip-name'
      name.textContent = userName
      chip.appendChild(name)

      return chip
    }

    function createMobilePendingButton () {
      var pendingLink = document.querySelector('.wt-secondary-navigation a.wt-pending, #secondaryMenu a.wt-pending, #secondaryMenuM a.wt-pending, .wt-secondary-navigation a[href*="pending"], #secondaryMenu a[href*="pending"], #secondaryMenuM a[href*="pending"]')
      if (!pendingLink) {
        return null
      }

      var pendingHref = pendingLink.getAttribute('href')
      if (!pendingHref) {
        return null
      }

      var pendingLabel = (pendingLink.textContent || '').replace(/\s+/g, ' ').trim() || 'Pending changes'
      var pendingButton = document.createElement('a')
      pendingButton.className = 'navbar-toggler mn-mobile-pending-btn'
      pendingButton.href = pendingHref
      pendingButton.setAttribute('aria-label', pendingLabel)
      pendingButton.title = pendingLabel

      var iconElement = pendingLink.querySelector('i[class*="fa-"], svg, img')
      if (iconElement) {
        pendingButton.appendChild(iconElement.cloneNode(true))
      } else {
        pendingButton.appendChild(createIcon('fa-solid fa-clock-rotate-left'))
      }

      return pendingButton
    }

    var mobilePendingButton = createMobilePendingButton()
    if (mobilePendingButton) {
      toggleBar.appendChild(mobilePendingButton)
    }

    var mobileUserChip = createMobileUserChip()
    if (mobileUserChip) {
      toggleBar.appendChild(mobileUserChip)
    }

    if (mobileUserChip) {
      var myMenuItem = secondaryNav.querySelector('.menu-mymenu')
      if (myMenuItem) {
        var secondaryNavList = secondaryNav.querySelector('.nav')
        var myMenuDropdown = myMenuItem.querySelector('.dropdown-menu')

        if (secondaryNavList && myMenuDropdown) {
          myMenuDropdown.querySelectorAll('.dropdown-item').forEach(function (item) {
            var href = item.getAttribute('href')
            if (!href) {
              return
            }

            var rowItem = document.createElement('li')
            rowItem.className = 'nav-item mn-mobile-user-subitem'

            var rowLink = document.createElement('a')
            rowLink.className = 'nav-link'
            rowLink.href = href
            copyMenuItemContent(item, rowLink)

            var itemTarget = item.getAttribute('target')
            if (itemTarget) {
              rowLink.setAttribute('target', itemTarget)
            }

            var itemRel = item.getAttribute('rel')
            if (itemRel) {
              rowLink.setAttribute('rel', itemRel)
            }

            rowItem.appendChild(rowLink)
            secondaryNavList.insertBefore(rowItem, myMenuItem.nextSibling)
          })
        }

        myMenuItem.remove()
      }
    }

    if (mobilePendingButton) {
      var duplicatePendingLink = secondaryNav.querySelector('a.wt-pending, a[href*="pending"]')
      if (duplicatePendingLink) {
        var duplicatePendingItem = duplicatePendingLink.closest('li, .nav-item, .dropdown')
        if (duplicatePendingItem) {
          duplicatePendingItem.remove()
        } else {
          duplicatePendingLink.remove()
        }
      }
    }

    var siteTitle = headerRow.querySelector('.wt-site-title')
    var headerSearch = headerRow.querySelector('.wt-header-search')

    headerRow.classList.add('mn-mobile-header-stack')
    if (siteTitle) {
      siteTitle.classList.add('mn-mobile-row-title')
    }
    if (headerSearch) {
      headerSearch.id = headerSearch.id || 'mnMobileSearchRow'
      headerSearch.classList.add('mn-mobile-row-search')
      searchBtn.setAttribute('aria-controls', headerSearch.id)
    }

    if (siteTitle) {
      headerRow.appendChild(siteTitle)
    }
    if (headerSearch) {
      headerRow.appendChild(headerSearch)
    }
    headerRow.appendChild(toggleBar)

    var hasBootstrapCollapse = typeof bootstrap !== 'undefined' && !!bootstrap.Collapse
    var primaryCollapse = hasBootstrapCollapse ? bootstrap.Collapse.getOrCreateInstance(primaryNav, { toggle: false }) : null
    var secondaryCollapse = hasBootstrapCollapse ? bootstrap.Collapse.getOrCreateInstance(secondaryNav, { toggle: false }) : null

    function openPanel (nav, collapse) {
      if (hasBootstrapCollapse && collapse) {
        collapse.show()
      } else {
        nav.classList.add('show')
      }
    }

    function hidePanel (nav, collapse) {
      if (hasBootstrapCollapse && collapse) {
        collapse.hide()
      } else {
        nav.classList.remove('show')
      }
    }

    function isOpen (nav) {
      return nav.classList.contains('show')
    }

    function showSearch () {
      if (headerSearch) {
        headerSearch.classList.add('show')
      }
    }

    function hideSearch () {
      if (headerSearch) {
        headerSearch.classList.remove('show')
      }
    }

    function isSearchOpen () {
      return headerSearch ? headerSearch.classList.contains('show') : false
    }

    primaryNav.addEventListener('shown.bs.collapse', function () { setExpanded(primaryBtn, true) })
    primaryNav.addEventListener('hidden.bs.collapse', function () { setExpanded(primaryBtn, false) })
    secondaryNav.addEventListener('shown.bs.collapse', function () { setExpanded(mobileUserChip, true) })
    secondaryNav.addEventListener('hidden.bs.collapse', function () { setExpanded(mobileUserChip, false) })

    if (!hasBootstrapCollapse) {
      setExpanded(primaryBtn, false)
    }

    primaryBtn.addEventListener('click', function () {
      if (isOpen(primaryNav)) {
        hidePanel(primaryNav, primaryCollapse)
        setExpanded(primaryBtn, false)
        return
      }

      hidePanel(secondaryNav, secondaryCollapse)
      hideSearch()
      setExpanded(searchBtn, false)
      openPanel(primaryNav, primaryCollapse)

      if (!hasBootstrapCollapse) {
        setExpanded(primaryBtn, true)
      }
    })

    if (mobileUserChip) {
      mobileUserChip.addEventListener('click', function () {
        if (isOpen(secondaryNav)) {
          hidePanel(secondaryNav, secondaryCollapse)
          setExpanded(mobileUserChip, false)
          return
        }

        hidePanel(primaryNav, primaryCollapse)
        hideSearch()
        setExpanded(searchBtn, false)
        openPanel(secondaryNav, secondaryCollapse)

        if (!hasBootstrapCollapse) {
          setExpanded(primaryBtn, false)
          setExpanded(mobileUserChip, true)
        }
      })
    }

    searchBtn.addEventListener('click', function () {
      if (isSearchOpen()) {
        hideSearch()
        setExpanded(searchBtn, false)
        return
      }

      hidePanel(primaryNav, primaryCollapse)
      hidePanel(secondaryNav, secondaryCollapse)
      if (!hasBootstrapCollapse) {
        setExpanded(primaryBtn, false)
      }
      showSearch()
      setExpanded(searchBtn, true)
    })
  }

  function initNoctisTheme () {
    initHeaderCollapses()
    initScrollPadding()
    highlightActiveNavItems()
    initAurora()
    initModalBackdropCleanup()
    initMobileBootstrapMenu()
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNoctisTheme, { once: true })
  } else {
    initNoctisTheme()
  }

  window.addEventListener('load', function () {
    initModalBackdropCleanup()
    initMobileBootstrapMenu()
  })

  initLeafletRefresh()
})()
