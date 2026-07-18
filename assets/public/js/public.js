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
            this.config = window.seoCampaignHubPublic || {};
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
                        $counter.html('<span class="expired">' + ($counter.data('expired-text') || 'Expired') + '</span>');
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

                updateTimer();
                setInterval(updateTimer, 1000);
            });
        }

        /**
         * Setup analytics tracking
         */
        setupAnalytics() {
            if (!this.config.tracking) {
                return;
            }

            this.trackEvent('page_view', {
                landing_page: window.location.href,
                event_name: document.title,
                post_id: this.config.postId || 0,
                campaign_id: this.config.campaignId || 0,
                offer_id: this.config.offerId || 0
            });

            let startTime = Date.now();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    const timeOnPage = Math.round((Date.now() - startTime) / 1000);
                    if (timeOnPage > 5) {
                        this.trackEvent('time_on_page', {
                            time_on_page: timeOnPage,
                            landing_page: window.location.href,
                            post_id: this.config.postId || 0
                        });
                    }
                }
            });

            let maxScroll = 0;
            let scrollTimeout;

            $(window).on('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const scrollTop = $(window).scrollTop();
                    const docHeight = $(document).height() - $(window).height();
                    if (docHeight <= 0) {
                        return;
                    }
                    const scrollPercent = Math.round((scrollTop / docHeight) * 100);

                    if (scrollPercent > maxScroll) {
                        maxScroll = scrollPercent;
                        if (maxScroll > 0 && maxScroll % 25 === 0) {
                            this.trackEvent('scroll', {
                                scroll_depth: maxScroll,
                                landing_page: window.location.href,
                                post_id: this.config.postId || 0
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
            if (!this.config.ajaxUrl || !this.config.tracking) {
                return;
            }

            const payload = Object.assign({}, data);
            if (!payload.landing_page) {
                payload.landing_page = window.location.href;
            }

            // Capture UTM from the original page, not admin-ajax.php.
            const params = new URLSearchParams(window.location.search);
            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach((key) => {
                if (params.get(key) && !payload[key]) {
                    payload[key] = params.get(key);
                }
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'seo_campaign_hub_track',
                    event_type: eventType,
                    data: JSON.stringify(payload),
                    nonce: this.config.nonce
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
            let bottomReached = false;
            const self = this;

            $(window).on('scroll', function() {
                if (bottomReached || !self.config.tracking) {
                    return;
                }

                const scrollTop = $(window).scrollTop();
                const docHeight = $(document).height();
                const windowHeight = $(window).height();

                if (scrollTop + windowHeight >= docHeight - 100) {
                    bottomReached = true;
                    self.trackEvent('scroll', {
                        scroll_depth: 100,
                        event_name: 'scroll_bottom',
                        landing_page: window.location.href
                    });
                }
            });
        }

        /**
         * Setup click tracking
         */
        setupClickTracking() {
            const self = this;

            $(document).on('click', '.sch-offer-link, .sch-cta, .sch-short-link', function() {
                const $link = $(this);
                self.trackEvent('click', {
                    landing_page: $link.attr('href'),
                    event_name: ($link.text() || '').trim() || 'cta_click',
                    post_id: self.config.postId || 0,
                    offer_id: $link.data('offer-id') || self.config.offerId || 0,
                    campaign_id: $link.data('campaign-id') || self.config.campaignId || 0,
                    meta_data: {
                        link_text: ($link.text() || '').trim(),
                        type: $link.data('type') || 'link'
                    }
                });
            });

            $(document).on('click', 'a[href^="http"]', function() {
                const $link = $(this);
                if ($link.is('.sch-offer-link, .sch-cta, .sch-short-link')) {
                    return;
                }
                const href = $link.attr('href') || '';

                if (href.indexOf(window.location.hostname) === -1) {
                    self.trackEvent('click', {
                        landing_page: href,
                        event_name: 'external_link',
                        meta_data: {
                            link_text: ($link.text() || '').trim()
                        }
                    });
                }
            });

            $(document).on('click', 'a[href^="mailto:"]', function() {
                const $link = $(this);
                self.trackEvent('click', {
                    event_name: 'email_link',
                    meta_data: {
                        email: ($link.attr('href') || '').replace('mailto:', ''),
                        link_text: ($link.text() || '').trim()
                    }
                });
            });
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        window.seoCampaignHubTracker = new SEOPublic();
    });

})(jQuery);
