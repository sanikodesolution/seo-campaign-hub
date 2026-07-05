/**
 * SEO Campaign Hub Public JavaScript
 *
 * @package SEO_Campaign_Hub
 */

(function($) {
    'use strict';

    /**
     * Public class
     */
    class SEOPublic {
        constructor() {
            this.init();
        }

        /**
         * Initialize public functionality
         */
        init() {
            this.setupCounters();
            this.setupAnalytics();
            this.setupLazyLoading();
            this.setupScrollTracking();
            this.setupClickTracking();
        }

        /**
         * Setup countdown timers
         */
        setupCounters() {
            $('.sch-counter-timer').each(function() {
                const $counter = $(this);
                const targetDate = $counter.data('target');
                const format = $counter.data('format') || 'days-hours-minutes-seconds';
                
                if (!targetDate) {
                    return;
                }

                const target = new Date(targetDate).getTime();

                const updateTimer = () => {
                    const now = new Date().getTime();
                    const distance = target - now;

                    if (distance < 0) {
                        $counter.html('<span class="expired">' + $counter.data('expired-text') || 'Expired' + '</span>');
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    let html = '';
                    
                    if (format.includes('days')) {
                        html += `<span class="counter-days"><span class="number">${days}</span> <span class="label">Days</span></span>`;
                    }
                    if (format.includes('hours')) {
                        html += `<span class="counter-hours"><span class="number">${String(hours).padStart(2, '0')}</span> <span class="label">Hours</span></span>`;
                    }
                    if (format.includes('minutes')) {
                        html += `<span class="counter-minutes"><span class="number">${String(minutes).padStart(2, '0')}</span> <span class="label">Minutes</span></span>`;
                    }
                    if (format.includes('seconds')) {
                        html += `<span class="counter-seconds"><span class="number">${String(seconds).padStart(2, '0')}</span> <span class="label">Seconds</span></span>`;
                    }

                    $counter.html(html);
                };

                // Update every second
                updateTimer();
                setInterval(updateTimer, 1000);
            });
        }

        /**
         * Setup analytics tracking
         */
        setupAnalytics() {
            if (!seoCampaignHubPublic || !seoCampaignHubPublic.tracking) {
                return;
            }

            // Track page view
            this.trackEvent('page_view', {
                url: window.location.href,
                title: document.title
            });

            // Track time on page
            let startTime = Date.now();
            
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    const timeOnPage = Math.round((Date.now() - startTime) / 1000);
                    if (timeOnPage > 5) {
                        this.trackEvent('time_on_page', {
                            seconds: timeOnPage,
                            url: window.location.href
                        });
                    }
                }
            });

            // Track scroll depth
            let maxScroll = 0;
            let scrollTimeout;

            $(window).on('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const scrollTop = $(window).scrollTop();
                    const docHeight = $(document).height() - $(window).height();
                    const scrollPercent = Math.round((scrollTop / docHeight) * 100);

                    if (scrollPercent > maxScroll) {
                        maxScroll = scrollPercent;
                        if (maxScroll > 0 && maxScroll % 25 === 0) {
                            this.trackEvent('scroll', {
                                depth: maxScroll,
                                url: window.location.href
                            });
                        }
                    }
                }, 500);
            });
        }

        /**
         * Track event
         */
        trackEvent(eventType, data = {}) {
            if (!seoCampaignHubPublic || !seoCampaignHubPublic.ajaxUrl) {
                return;
            }

            $.ajax({
                url: seoCampaignHubPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seo_campaign_hub_track',
                    event_type: eventType,
                    data: JSON.stringify(data),
                    nonce: seoCampaignHubPublic.nonce
                }
            });
        }

        /**
         * Setup lazy loading
         */
        setupLazyLoading() {
            if ('IntersectionObserver' in window) {
                const lazyImages = document.querySelectorAll('img[data-src]');
                
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.add('loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                lazyImages.forEach((img) => {
                    imageObserver.observe(img);
                });
            } else {
                // Fallback for older browsers
                $('img[data-src]').each(function() {
                    const $img = $(this);
                    $img.attr('src', $img.data('src'));
                    $img.removeAttr('data-src');
                });
            }
        }

        /**
         * Setup scroll tracking
         */
        setupScrollTracking() {
            // Track when user reaches bottom of page
            let bottomReached = false;

            $(window).on('scroll', function() {
                if (bottomReached) {
                    return;
                }

                const scrollTop = $(window).scrollTop();
                const docHeight = $(document).height();
                const windowHeight = $(window).height();

                if (scrollTop + windowHeight >= docHeight - 100) {
                    bottomReached = true;
                    this.trackEvent('scroll_bottom', {
                        url: window.location.href
                    });
                }
            });
        }

        /**
         * Setup click tracking
         */
        setupClickTracking() {
            // Track clicks on offers and CTAs
            $(document).on('click', '.sch-offer-link, .sch-cta, .sch-short-link', function() {
                const $link = $(this);
                const data = {
                    url: $link.attr('href'),
                    text: $link.text().trim(),
                    type: $link.data('type') || 'link'
                };

                this.trackEvent('click', data);
            });

            // Track external link clicks
            $(document).on('click', 'a[href^="http"]', function() {
                const $link = $(this);
                const href = $link.attr('href');
                
                if (href.indexOf(window.location.hostname) === -1) {
                    this.trackEvent('external_link', {
                        url: href,
                        text: $link.text().trim()
                    });
                }
            });

            // Track email link clicks
            $(document).on('click', 'a[href^="mailto:"]', function() {
                const $link = $(this);
                this.trackEvent('email_link', {
                    email: $link.attr('href').replace('mailto:', ''),
                    text: $link.text().trim()
                });
            });
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        window.seoCampaignHubPublic = new SEOPublic();
    });

})(jQuery);