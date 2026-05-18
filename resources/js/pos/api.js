const Api = {
    async findProduct(code) {
        const res = await fetch(`/api/pos/product?q=${encodeURIComponent(code)}`, {
            credentials: 'same-origin'
        });

        if (!res.ok) {
            throw new Error('Produk tidak ditemukan');
        }

        return res.json();
    },

    async checkout(cart) {
        // loading swal 2
        Swal.fire({
            title: 'Memproses pembayaran...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        const res = await fetch('/pos/checkout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ cart })
        });

        if (!res.ok) {
            throw new Error('Checkout gagal');
        }

        Swal.close();
        return res.json();
    },

    async printThermal(saleId) {
        const res = await fetch(`/sales/${saleId}/print`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
    }
};
export default Api;