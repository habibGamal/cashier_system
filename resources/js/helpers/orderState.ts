export const orderStatus = (status: string) => {
    switch (status) {
        case 'pending':
            return { text: 'في الإنتظار', color: 'orange' };
        case 'processing':
            return { text: 'تحت التشغيل', color: 'green' };
        case 'out_for_delivery':
            return { text: 'في طريق التوصيل', color: 'purple' };
        case 'completed':
            return { text: 'مكتمل', color: 'grey' };
        case 'cancelled':
            return { text: 'ملغي', color: 'red' };
        default:
            return { text: status, color: 'default' };
    }
};
