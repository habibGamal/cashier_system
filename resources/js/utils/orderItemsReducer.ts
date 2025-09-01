import { OrderItemData, OrderItemAction, User, Product } from '@/types';

// Barcode parser function
const parseBarcode = (barcode: string, scalePrefix: string = '23'): {
    productBarcode: string;
    weightKg?: number;
    checksum: number;
    isScaleBarcode: boolean
} | null => {
    // Remove any whitespace and validate length
    const cleanBarcode = barcode.trim();

    // Validate that all characters are digits
    if (!/^\d+$/.test(cleanBarcode)) {
        return null;
    }

    // Check if this is a scale (weighted) barcode
    const isScaleBarcode = cleanBarcode.startsWith(scalePrefix);

    if (isScaleBarcode) {
        // Scale barcode format: [prefix][PLU][weight][checksum]
        // For example: 2300001002498
        // 23 = prefix (2 digits)
        // 2300001 = PLU/product code (7 digits)
        // 00249 = weight in format dependent on scale config (5 digits)
        // 8 = checksum (1 digit)

        const prefixLength = scalePrefix.length;
        const productCode = cleanBarcode.substr(0, 7);
        const weightData = cleanBarcode.substr(7, 5);
        const checksum = cleanBarcode.substr(12, 1);

        // Convert weight data - assuming it represents weight in grams or with 2 decimal places
        // For example: 00249 could be 249g depending on scale configuration
        // We'll assume it's in format XXX.XX (so 00249 = 249g)
        const weightKg = parseInt(weightData) / 1000;

        return {
            productBarcode: productCode,
            weightKg,
            checksum: parseInt(checksum),
            isScaleBarcode: true
        };
    } else {
        // Standard EAN-13 product barcode
        // All 13 digits represent the product (manufacturer + product + checksum)
        const productBarcode = cleanBarcode;

        return {
            productBarcode,
            checksum: parseInt("1"),
            isScaleBarcode: false
        };
    }
};

export const orderItemsReducer = (
    state: OrderItemData[],
    action: OrderItemAction
): OrderItemData[] => {
    let canChange = true;
    let limit = 0;

    if (action.type !== 'add' && action.type !== 'init' && action.type !== 'addByBarcode') {
        const isAdmin = action.user.role === 'admin';
        const orderItem = action.id
            ? state.find((item) => item.product_id === action.id!)
            : null;
        const itemSavedBefore = orderItem?.initial_quantity !== null && orderItem?.initial_quantity !== undefined;

        if (!isAdmin && itemSavedBefore) {
            canChange = false;
            limit = orderItem.initial_quantity!;
        }
    }

    switch (action.type) {
        case 'add': {
            // Check if the order item already exists
            const existingItem = state.find(
                (item) => item.product_id === action.orderItem.product_id
            );
            if (existingItem) {
                // If it exists, increment the quantity
                return state.map((item) =>
                    item.product_id === action.orderItem.product_id
                        ? { ...item, quantity: item.quantity + 1 }
                        : item
                );
            }
            return [...state, action.orderItem];
        }

        case 'remove':
            return canChange
                ? state.filter((item) => item.product_id !== action.id)
                : state;

        case 'increment':
            return state.map((item) =>
                item.product_id === action.id
                    ? { ...item, quantity: item.quantity + 1 }
                    : item
            );

        case 'decrement': {
            const orderItem = state.find((item) => item.product_id === action.id);
            if (!canChange && orderItem && orderItem.quantity === limit) return state;

            return state.map((item) => {
                if (item.product_id !== action.id) return item;
                if (item.quantity === 1) {
                    return item;
                }
                return { ...item, quantity: item.quantity - 1 };
            });
        }

        case 'changeQuantity': {
            if (!canChange && action.quantity < limit) {
                action.quantity = limit;
            }
            return state.map((item) => {
                if (item.product_id !== action.id) return item;
                // Remove Math.floor to allow decimal quantities
                return { ...item, quantity: action.quantity };
            });
        }

        case 'changeNotes': {
            return state.map((item) => {
                if (item.product_id !== action.id) return item;
                return { ...item, notes: action.notes };
            });
        }

        case 'delete':
            return canChange
                ? state.filter((item) => item.product_id !== action.id)
                : state;

        case 'init':
            return action.orderItems;

        case 'addByBarcode': {
            const parsedBarcode = parseBarcode(action.barcode, action.scalePrefix);
            if (!parsedBarcode) {
                // Invalid barcode format
                return state;
            }

            // Find product by barcode
            let product: Product | undefined;

            if (parsedBarcode.isScaleBarcode) {
                // For scale barcodes, find product by PLU/product code
                product = action.products.find(p => p.barcode === parsedBarcode.productBarcode);
            } else {
                // For standard barcodes, find product by full barcode
                product = action.products.find(p => p.barcode === parsedBarcode.productBarcode);
            }

            if (!product) {
                // Product not found
                return state;
            }

            // Check if the order item already exists
            const existingItemIndex = state.findIndex(item => item.product_id === product.id);

            if (existingItemIndex !== -1) {
                // If it exists, add the weight/quantity to the existing quantity
                const quantityToAdd = parsedBarcode.isScaleBarcode && parsedBarcode.weightKg
                    ? parsedBarcode.weightKg
                    : 1;

                return state.map((item, index) =>
                    index === existingItemIndex
                        ? { ...item, quantity: item.quantity + quantityToAdd }
                        : item
                );
            } else {
                // Add new item
                const quantity = parsedBarcode.isScaleBarcode && parsedBarcode.weightKg
                    ? parsedBarcode.weightKg
                    : 1;

                const newItem: OrderItemData = {
                    product_id: product.id,
                    name: product.name,
                    price: product.price,
                    quantity: quantity,
                    notes: ``,
                    initial_quantity: undefined,
                };
                return [...state, newItem];
            }
        }

        default:
            throw new Error('Action not found');
    }
};
