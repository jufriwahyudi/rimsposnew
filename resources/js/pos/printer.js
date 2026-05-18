// ===============================
// CONFIG
// ===============================
const LINE_WIDTH = 48;

// ===============================
// HELPER TEXT
// ===============================
function line(char = '-') {
    return char.repeat(LINE_WIDTH) + '\n';
}

function center(text) {
    const pad = Math.max(0, Math.floor((LINE_WIDTH - text.length) / 2));
    return ' '.repeat(pad) + text + '\n';
}

function leftRight(left, right) {
    const space = LINE_WIDTH - left.length - right.length;
    return left + ' '.repeat(Math.max(0, space)) + right + '\n';
}

function wordWrap(text) {
    let result = '';
    for (let i = 0; i < text.length; i += LINE_WIDTH) {
        result += text.substring(i, i + LINE_WIDTH) + '\n';
    }
    return result;
}

function money(value) {
    return 'Rp ' + Number(value).toLocaleString('id-ID');
}

// ===============================
// LOAD IMAGE → BASE64 (PURE)
// ===============================
function loadImageBase64(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';

        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const base64 = canvas
                .toDataURL('image/png')
                .replace(/^data:image\/png;base64,/, '');

            resolve(base64);
        };

        img.onerror = reject;
        img.src = url;
    });
}

// ===============================
// INIT QZ TRAY
// ===============================
// ===============================
// INIT QZ TRAY (GLOBAL INIT)
// ===============================
async function initQZ() {

    if (!window.qz) {
        console.warn("QZ Tray not available");
        return;
    }

    // Hindari init berkali-kali
    if (window.qzInitialized) return;
    window.qzInitialized = true;

    qz.security.setCertificatePromise((resolve, reject) => {
        fetch("/assets/override.crt", { cache: 'no-store' })
            .then(resp => resp.ok ? resp.text() : Promise.reject(resp.text()))
            .then(resolve)
            .catch(reject);
    });

    qz.security.setSignatureAlgorithm("SHA512");

    qz.security.setSignaturePromise(toSign => (resolve, reject) => {
        fetch(`/sign-message.php?request=${toSign}`, { cache: 'no-store' })
            .then(resp => resp.ok ? resp.text() : Promise.reject(resp.text()))
            .then(resolve)
            .catch(reject);
    });

    if (!qz.websocket.isActive()) {
        try {
            await qz.websocket.connect();
            console.log("QZ connected");
        } catch (err) {
            console.error("Connection error:", err);
        }
    }
}
// ===============================
// PRINT RECEIPT
// ===============================
async function printReceiptPrinter(data) {
    if (!window.qz) {
        alert('QZ Tray belum siap');
        return;
    }

    // ========================================
    // CONNECT TO QZ (SETELAH CERT/ SIGNATURE READY)
    // ========================================
    if (!qz.websocket.isActive()) {
        await qz.websocket.connect();
    }

    // ========================================
    // GET PRINTER
    // ========================================
    const printer = await qz.printers.getDefault();
    const config = qz.configs.create(printer, {
        encoding: 'UTF-8',
        scaleContent: true
    });

    let output = [];

    // ===============================
    // INIT PRINTER
    // ===============================
    output.push('\x1B\x40'); // ESC @

    // ===============================
    // LOGO (ESC/POS IMAGE)
    // ===============================
    // let logoBase64 = null;
    // if (data.store.logo) {
    //     logoBase64 = await loadImageBase64(data.store.logo);

    //     output.push({
    //         type: 'image',
    //         format: 'png',
    //         data: logoBase64,
    //         options: {
    //             language: 'escpos',
    //             align: 'center'
    //         }
    //     });

    //     output.push('\n');
    // }

    // ===============================
    // HEADER
    // ===============================
    output.push('\x1B\x61\x01'); // center
    output.push('\x1B\x45\x01'); // bold
    output.push(data.store.name + '\n');
    output.push('\x1B\x45\x00'); // bold off

    output.push(data.store.address + '\n');
    output.push(data.store.city + '\n');
    output.push(`Tel: ${data.store.phone}\n`);
    output.push('\x1B\x61\x00'); // left

    output.push(line());

    // ===============================
    // TRANSACTION INFO
    // ===============================
    output.push(leftRight('No', data.transaction.invoice));
    output.push(leftRight('Tanggal', data.transaction.date));
    output.push(leftRight('Kasir', data.transaction.cashier));
    output.push(leftRight('Pelanggan', data.transaction.customer));
    output.push(leftRight('Status', data.transaction.status));
    output.push(line());

    // ===============================
    // ITEMS
    // ===============================
    data.items.forEach(item => {
        // output.push(item.name + '\n');
        output.push(
            leftRight(
                item.name,
                item.sku
            )
        );
        output.push(
            leftRight(
                `  ${item.qty} x ${money(item.price)}`,
                money(item.qty * item.price)
            )
        );
    });

    output.push(line());

    // ===============================
    // SUMMARY
    // ===============================
    output.push(leftRight('Subtotal', money(data.summary.subtotal)));
    output.push(leftRight('Diskon', money(data.summary.discount)));

    output.push('\x1B\x45\x01'); // bold
    output.push(leftRight('TOTAL', money(data.summary.total)));
    output.push('\x1B\x45\x00'); // bold off

    output.push(leftRight('Bayar', money(data.summary.paid)));
    output.push(leftRight('Kembali', money(data.summary.change)));
    output.push(line());

    // ===============================
    // FOOTER
    // ===============================
    output.push('\x1B\x61\x01'); // center
    output.push('Jazakumullah khairan\n');
    output.push('\x1B\x61\x00'); // left

    output.push(
        wordWrap('Barang yang sudah dibeli dapat ditukar maksimal dalam 5 hari kerja.')
    );

    output.push('-' + '\n');
    output.push('-' + '\n');
    output.push('-' + '\n');
    // ===============================
    // CUT
    // ===============================
    output.push('\x1D\x56\x00'); // GS V 0

    // const datas = [

    //     // === IMAGE WAJIB DI DEPAN ===
    //     {
    //         type: 'image',
    //         format: 'png',
    //         data: logoBase64,
    //         options: {
    //             language: 'escpos',
    //             align: 'center'
    //         }
    //     },
    //     '\n'];
    // ===============================
    // PRINT
    // ===============================
    return qz.print(config, output);
}

// ===============================
// EXPORT
// ===============================
window.Printer = {
    initQZ,
    printReceiptPrinter
};
