document.addEventListener('DOMContentLoaded', function() {
    // Initialize QR Code Styling
    const qrCode = new QRCodeStyling({
        width: 300,
        height: 300,
        type: "svg",
        data: "https://google.com",
        image: "",
        dotsOptions: {
            color: "#000000",
            type: "square"
        },
        backgroundOptions: {
            color: "#ffffff",
        },
        imageOptions: {
            crossOrigin: "anonymous",
            margin: 10
        },
        cornersSquareOptions: {
            type: "square",
            color: "#000000"
        },
        cornersDotOptions: {
            type: "square",
            color: "#000000"
        }
    });

    // Initial Render
    qrCode.append(document.getElementById("qr-canvas"));

    // Tab Switching Logic
    const tabs = document.querySelectorAll('.qr-tab-btn');
    const contents = document.querySelectorAll('.qr-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all
            tabs.forEach(t => t.classList.remove('active'));
            contents.forEach(c => c.classList.remove('active'));

            // Add active class to clicked
            tab.classList.add('active');
            const tabId = tab.getAttribute('data-tab');
            document.getElementById(`tab-${tabId}`).classList.add('active');
        });
    });

    // Content Type Switching
    const typeBtns = document.querySelectorAll('.qr-type-btn');
    const typeForms = document.querySelectorAll('.qr-content-form');
    let currentType = 'url';

    typeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class
            typeBtns.forEach(b => b.classList.remove('active'));
            typeForms.forEach(f => f.classList.remove('active'));

            // Add active class
            btn.classList.add('active');
            currentType = btn.getAttribute('data-type');
            document.getElementById(`form-${currentType}`).classList.add('active');
            
            updateQR();
        });
    });

    // Update Logic
    function updateQR() {
        let qrData = '';

        // Format data based on type
        switch(currentType) {
            case 'url':
                qrData = document.getElementById('qr-url').value;
                break;
            case 'text':
                qrData = document.getElementById('qr-text-content').value;
                break;
            case 'email':
                const email = document.getElementById('qr-email-to').value;
                const subject = document.getElementById('qr-email-subject').value;
                const body = document.getElementById('qr-email-body').value;
                qrData = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
                break;
            case 'whatsapp':
                const code = document.getElementById('qr-wa-code').value;
                const phone = document.getElementById('qr-wa-phone').value.replace(/\D/g, '');
                const message = document.getElementById('qr-wa-message').value;
                qrData = `https://wa.me/${code}${phone}?text=${encodeURIComponent(message)}`;
                break;
        }

        const options = {
            data: qrData,
            dotsOptions: {
                color: document.getElementById('qr-dots-color').value,
                type: document.getElementById('qr-dots-type').value
            },
            backgroundOptions: {
                color: document.getElementById('qr-bg-color').value,
            },
            cornersSquareOptions: {
                type: document.getElementById('qr-corners-square-type').value,
                color: document.getElementById('qr-marker-border-color').value
            },
            cornersDotOptions: {
                type: document.getElementById('qr-corners-dot-type').value,
                color: document.getElementById('qr-marker-center-color').value
            },
            image: document.getElementById('qr-logo-url').value,
            imageOptions: {
                imageSize: parseFloat(document.getElementById('qr-logo-size').value),
                margin: parseInt(document.getElementById('qr-logo-margin').value)
            },
            qrOptions: {
                errorCorrectionLevel: document.getElementById('qr-error-correction').value
            }
        };

        qrCode.update(options);
    }

    // Media Uploader
    const uploadBtn = document.getElementById('qr-upload-logo');
    if (uploadBtn) {
        let file_frame;
        uploadBtn.addEventListener('click', (event) => {
            event.preventDefault();

            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: 'Select a Logo',
                button: {
                    text: 'Use this logo',
                },
                multiple: false
            });

            // When an image is selected, run a callback.
            file_frame.on('select', () => {
                const attachment = file_frame.state().get('selection').first().toJSON();
                document.getElementById('qr-logo-url').value = attachment.url;
                updateQR();
            });

            // Finally, open the modal
            file_frame.open();
        });
    }

    // Event Listeners for Inputs
    const inputs = [
        'qr-url', 'qr-bg-color', 'qr-dots-color', 'qr-marker-border-color', 
        'qr-marker-center-color', 'qr-dots-type', 'qr-corners-square-type', 
        'qr-corners-dot-type', 'qr-logo-url', 'qr-logo-size', 'qr-logo-margin',
        'qr-error-correction',
        // New inputs
        'qr-text-content', 'qr-email-to', 'qr-email-subject', 'qr-email-body',
        'qr-wa-code', 'qr-wa-phone', 'qr-wa-message'
    ];

    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateQR);
            el.addEventListener('change', updateQR);
        }
    });

    // Download Buttons
    document.getElementById('qr-download').addEventListener('click', () => {
        qrCode.download({ name: "qr-code", extension: "png" });
    });
    
    document.getElementById('qr-download-svg').addEventListener('click', () => {
        qrCode.download({ name: "qr-code", extension: "svg" });
    });
});
