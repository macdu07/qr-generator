/**
 * QR Generator Admin Dashboard Script
 */

jQuery(document).ready(function ($) {

    // View Details Modal
    $('.qr-view-details').on('click', function () {
        const type = $(this).data('type');
        const content = $(this).data('content');
        const customization = $(this).data('customization');

        $('#detail-type').text(type.toUpperCase());
        $('#detail-content').text(content);

        try {
            const custom = typeof customization === 'string' ? JSON.parse(customization) : customization;
            let customHtml = '<ul>';
            for (const [key, value] of Object.entries(custom)) {
                if (typeof value === 'object') {
                    customHtml += `<li><strong>${key}:</strong> ${JSON.stringify(value)}</li>`;
                } else {
                    customHtml += `<li><strong>${key}:</strong> ${value}</li>`;
                }
            }
            customHtml += '</ul>';
            $('#detail-customization').html(customHtml);
        } catch (e) {
            $('#detail-customization').text('No customization data');
        }

        $('#qr-details-modal').fadeIn();
    });

    // Close Modal
    $('.qr-modal-close, .qr-modal').on('click', function (e) {
        if (e.target === this) {
            $('#qr-details-modal').fadeOut();
        }
    });

    // Re-download QR Code
    $('.qr-download-btn').on('click', function () {
        const $btn = $(this);
        const qrData = $btn.data('qr-data');
        const customization = $btn.data('customization');
        const format = $btn.data('format');
        const originalText = $btn.text();

        // Disable button and show loading
        $btn.prop('disabled', true).text('Generating...');

        // Parse customization
        let custom = {};
        try {
            custom = typeof customization === 'string' ? JSON.parse(customization) : customization;
        } catch (e) {
            console.error('Error parsing customization:', e);
        }

        // Create QR Code instance
        const qrCode = new QRCodeStyling({
            width: 1000,
            height: 1000,
            type: format === 'svg' ? 'svg' : 'canvas',
            data: qrData,
            image: custom.logoUrl || "",
            dotsOptions: {
                color: custom.dotsColor || "#000000",
                type: custom.dotsType || "square"
            },
            backgroundOptions: {
                color: custom.backgroundColor || "#ffffff",
            },
            imageOptions: {
                crossOrigin: "anonymous",
                margin: parseInt(custom.logoMargin) || 10,
                imageSize: parseFloat(custom.logoSize) || 0.4
            },
            cornersSquareOptions: {
                type: custom.cornerSquareType || "square",
                color: custom.markerBorderColor || "#000000"
            },
            cornersDotOptions: {
                type: custom.cornerDotType || "square",
                color: custom.markerCenterColor || "#000000"
            },
            qrOptions: {
                errorCorrectionLevel: custom.errorCorrection || "Q"
            }
        });

        // Generate and download
        const container = document.createElement('div');
        container.style.display = 'none';
        document.body.appendChild(container);

        qrCode.append(container);

        // Small delay to ensure rendering
        setTimeout(() => {
            qrCode.download({
                name: "qr-code-from-history",
                extension: format === 'svg' ? 'svg' : 'png'
            });

            // Cleanup
            document.body.removeChild(container);

            // Re-enable button
            $btn.prop('disabled', false).text(originalText);
        }, 500);
    });
});
