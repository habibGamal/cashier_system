import { Order, OrderItemData } from '@/types';
import { usePage } from '@inertiajs/react';

export interface OrderTotals {
  subTotal: number;
  tax: number;
  service: number;
  discount: number;
  total: number;
}

export const calculateOrderTotals = (
  order: Order,
  orderItems: OrderItemData[]
): OrderTotals => {
  const subTotal = orderItems.reduce(
    (acc, item) => acc + (Number(item.price) || 0) * (Number(item.quantity) || 0),
    0
  );

  const tax = subTotal * 0; // Currently no tax

  let service = 0;
  if (order?.type === 'delivery') {
    service = Number(order.customer?.delivery_cost ?? 0);
  } else if (order?.type === 'dine_in') {
    service = subTotal * (Number(order.service_rate) || 0);
  }

  let discount = 0;
  const tempDiscountPercent = Number(order?.temp_discount_percent) || 0;
  const orderDiscount = Number(order?.discount) || 0;

  if (tempDiscountPercent > 0) {
    discount = subTotal * (tempDiscountPercent / 100);
  } else {
    discount = orderDiscount;
  }

  const total = Math.ceil(subTotal + tax + service - discount);

  return {
    subTotal,
    tax,
    service,
    discount,
    total,
  };
};

export const formatCurrency = (amount: number): string => {
    try {
        const { currency } = usePage().props as any;
        const decimals = currency?.decimals || 1;
        const symbol = currency?.symbol || 'ج.م';
        return `${Number(amount).toFixed(decimals)} ${symbol}`;
    } catch (error) {
        console.error('Error formatting currency:', error);
        return `0.0 ج.م`;
    }
};

export const getOrderStatusConfig = (status: string) => {
  switch (status) {
    case 'processing':
      return {
        color: 'green',
        text: 'تحت التشغيل',
      };
    case 'completed':
      return {
        color: 'grey',
        text: 'مكتمل',
      };
    case 'cancelled':
      return {
        color: 'red',
        text: 'ملغي',
      };
    default:
      return {
        color: 'default',
        text: status,
      };
  }
};

export const getOrderTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    dine_in: 'صالة',
    takeaway: 'تيك أواي',
    delivery: 'دليفري',
    companies: 'شركات',
    talabat: 'طلبات',
    direct_sale: 'بيع مباشر',
  };
  return labels[type] || type;
};

export const getPaymentMethodLabel = (method: string): string => {
  const labels: Record<string, string> = {
    cash: 'نقدي',
    card: 'بطاقة',
    talabat_card: 'بطاقة طلبات',
  };
  return labels[method] || method;
};
