import { Order } from '@/types';

export const orderHeader = (order: Order) => {
    const orderTypeText = order.type === 'web_delivery' ? 'ويب دليفري' :
                         order.type === 'web_takeaway' ? 'ويب تيك أواي' :
                         order.type === 'delivery' ? 'دليفري' :
                         order.type === 'takeaway' ? 'تيك أواي' :
                         order.type === 'dine_in' ? 'صالة' : order.type;

    return [
        {
            title: 'الطلبات',
        },
        {
            title: orderTypeText,
        },
        {
            title: `طلب رقم ${order.order_number}`,
        },
    ];
};
