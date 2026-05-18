const Cart = {
    create() {
        return {
            id: crypto.randomUUID(),
            items: [],
            subtotal: 0,
            transaction_discount_type: null, // percent | nominal
            transaction_discount_value: 0,
            transaction_discount: 0,
            discount_total: 0,
            total: 0,
            transaction_date: null,
            customer_name: 'Umum'
        };
    },

    addItem(cart, product) {
        const existing = cart.items.find(
            i => i.product_id === product.id
        );

        if (existing) {
            if (existing.qty + 1 > product.stok) {
                Swal.fire(
                    'Stok tidak cukup',
                    `Sisa stok: ${product.stok}`,
                    'warning'
                );
                return;
            }
            existing.qty++;
        } else {
            cart.items.push({
                key: crypto.randomUUID(),
                product_id: product.product_id,
                variant_id: product.id,
                sku: product.sku,
                name: product.name,
                variant: product.variant || '',
                price: parseFloat(product.price),
                qty: 1,
                stok: product.stok,
                discount_type: null, // percent | nominal
                discount_value: 0,
                discount_amount: 0,
                subtotal: parseFloat(product.price)
            });
        }

        this.recalculate(cart);
    },

    setItemDiscount(cart, productId, value) {
        const item = cart.items.find(i => i.product_id == productId);
        if (!item) return;

        if (value <= 100) {
            item.discount_type = 'percent';
            item.discount_value = value;
            item.discount_amount = item.price * item.qty * (value / 100);
        } else {
            item.discount_type = 'nominal';
            item.discount_value = value;
            item.discount_amount = value;
        }

        this.recalculate(cart);
    },

    updateQty(cart, index, qty) {
        if (qty < 1) qty = 1;

        if (qty > cart.items[index].stok) {
            Swal.fire(
                'Stok tidak cukup',
                `Sisa stok: ${cart.items[index].stok}`,
                'warning'
            );
            return;
        }

        cart.items[index].qty = qty;
        this.recalculate(cart);
    },

    removeItem(cart, index) {
        cart.items.splice(index, 1);
        this.recalculate(cart);
    },

    calculateItemSubtotal(item) {
        let total = item.price * item.qty;
        let discount = 0;

        if (item.discount_type === 'percent') {
            discount = total * (item.discount_value / 100);
        }

        if (item.discount_type === 'nominal') {
            discount = item.discount_value;
        }

        if (discount > total) discount = total;

        return total - discount;
    },

    calculateTransactionDiscount(cart) {
        let discount = 0;

        if (cart.transaction_discount_type === 'percent') {
            discount = cart.subtotal * (cart.transaction_discount_value / 100);
        }

        if (cart.transaction_discount_type === 'nominal') {
            discount = cart.transaction_discount_value;
        }

        if (discount > cart.subtotal) discount = cart.subtotal;

        return discount;
    },

    recalculate(cart) {
        let subtotal = 0;

        cart.items.forEach(item => {
            item.subtotal = this.calculateItemSubtotal(item);
            subtotal += item.subtotal;
        });

        cart.subtotal = subtotal;
        cart.transaction_discount = this.calculateTransactionDiscount(cart);
        cart.discount_total = cart.items.reduce((sum, item) => sum + item.discount_amount, 0) + cart.transaction_discount;
        cart.total = subtotal - cart.transaction_discount;

    }
};

export default Cart;
