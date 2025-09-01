import { Order, OrderItemData } from '@/types';

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
    (acc, item) => acc + item.price * item.quantity,
    0
  );

  const tax = subTotal * 0; // Currently no tax

  let service = 0;
  if (order.type === 'delivery') {
    service = Number(order.customer?.delivery_cost ?? 0);
  } else if (order.type === 'dine_in') {
    service = subTotal * order.service_rate!;
  }
  let discount = 0;
  if (order.temp_discount_percent > 0) {
    discount = subTotal * (order.temp_discount_percent / 100);
  } else {
    discount = order.discount;
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
        return `${Number(amount).toFixed(1)} ج.م`;
    } catch (error) {
        console.error('Error formatting currency:', error);
        return '0.0 ج.م';
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
