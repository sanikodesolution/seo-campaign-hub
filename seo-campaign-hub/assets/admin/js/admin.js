/**
 * SEO Campaign Hub Admin JavaScript
 *
 * @package SEO_Campaign_Hub
 */

(function($) {
    'use strict';

    /**
     * Admin class
     */
    class SEOAdmin {
        constructor() {
            this.init();
        }

        /**
         * Initialize admin functionality
         */
        init() {
            this.setupTabs();
            this.setupConfirmDelete();
            this.setupCopyToClipboard();
            this.setupAjaxForms();
            this.setupCharts();
            this.setupNotifications();
            this.setupDateTimePickers();
        }

        /**
         * Setup tab navigation
         */
        setupTabs() {
            $('.seo-campaign-hub-tabs').each(function() {
                const $tabs = $(this);
                const $tabContent = $tabs.next('.seo-campaign-hub-tab-content');
                
                $tabs.on('click', '.tab-link', function(e) {
                    e.preventDefault();
                    
                    const tabId = $(this).data('tab');
                    
                    // Update active tab
                    $tabs.find('.tab-link').removeClass('active');
                    $(this).addClass('active');
                    
                    // Show tab content
                    $tabContent.find('.tab-pane').hide();
                    $tabContent.find('#' + tabId).show();
                });
            });
        }

        /**
         * Setup confirm delete
         */
        setupConfirmDelete() {
            $('.seo-campaign-hub-delete').on('click', function(e) {
                if (!confirm(seoCampaignHub.strings.confirmDelete)) {
                    e.preventDefault();
                    return false;
                }
            });
        }

        /**
         * Setup copy to clipboard
         */
        setupCopyToClipboard() {
            $('.seo-campaign-hub-copy').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const text = $this.data('copy-text') || $this.text();
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showNotification(seoCampaignHub.strings.copied, 'success');
                    }).catch(() => {
                        this.fallbackCopy(text);
                    });
                } else {
                    this.fallbackCopy(text);
                }
            });
        }

        /**
         * Fallback copy method
         */
        fallbackCopy(text) {
            const $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            this.showNotification(seoCampaignHub.strings.copied, 'success');
        }

        /**
         * Setup AJAX forms
         */
        setupAjaxForms() {
            $('.seo-campaign-hub-ajax-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $form.find('[type="submit"]');
                const originalText = $submitBtn.text();
                
                // Show loading state
                $submitBtn.text(seoCampaignHub.strings.processing).prop('disabled', true);
                
                $.ajax({
                    url: seoCampaignHub.ajaxUrl,
                    type: 'POST',
                    data: $form.serialize() + '&action=seo_campaign_hub_ajax',
                    success: (response) => {
                        if (response.success) {
                            this.showNotification(response.data.message || seoCampaignHub.strings.saveSuccess, 'success');
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            }
                        } else {
                            this.showNotification(response.data.message || seoCampaignHub.strings.saveError, 'error');
                        }
                    },
                    error: () => {
                        this.showNotification(seoCampaignHub.strings.saveError, 'error');
                    },
                    complete: () => {
                        $submitBtn.text(originalText).prop('disabled', false);
                    }
                });
            });
        }

        /**
         * Setup charts
         */
        setupCharts() {
            // Wait for Chart.js to load
            if (typeof Chart === 'undefined') {
                return;
            }

            $('.seo-campaign-hub-chart').each(function() {
                const $chart = $(this);
                const ctx = $chart[0].getContext('2d');
                
                const config = {
                    type: $chart.data('type') || 'line',
                    data: {
                        labels: $chart.data('labels') || [],
                        datasets: [{
                            label: $chart.data('label') || 'Data',
                            data: $chart.data('values') || [],
                            backgroundColor: 'rgba(0, 124, 186, 0.2)',
                            borderColor: 'rgba(0, 124, 186, 1)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgba(0, 124, 186, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: $chart.data('show-legend') !== false,
                                position: 'bottom'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                };
                
                new Chart(ctx, config);
            });
        }

        /**
         * Setup notifications
         */
        setupNotifications() {
            // Auto-hide notifications after 5 seconds
            $('.seo-campaign-hub-notice').each(function() {
                const $notice = $(this);
                if ($notice.data('auto-hide') !== false) {
                    setTimeout(() => {
                        $notice.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 5000);
                }
            });
        }

        /**
         * Setup datetime pickers
         */
        setupDateTimePickers() {
            if (typeof jQuery.fn.datetimepicker !== 'undefined') {
                $('.seo-campaign-hub-datetime').datetimepicker({
                    format: 'Y-m-d H:i:s',
                    step: 30
                });
            }
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'success') {
            const $notice = $(`
                <div class="seo-campaign-hub-notice ${type}">
                    <p>${message}</p>
                </div>
            `);
            
            // Add to page
            const $container = $('.seo-campaign-hub-notifications');
            if ($container.length) {
                $container.prepend($notice);
            } else {
                $('.seo-campaign-hub-wrap').prepend($notice);
            }
            
            // Auto-hide
            setTimeout(() => {
                $notice.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        window.seoCampaignHubAdmin = new SEOAdmin();
    });

})(jQuery);