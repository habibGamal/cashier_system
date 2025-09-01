import React from 'react';
import { Order, OrderItemData } from '@/types';

export interface KitchenItemForPrint {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    notes?: string;
    initial_quantity?: number;
}

interface KitchenTemplateProps {
    printerId: string;
    order: Order;
    orderItems: KitchenItemForPrint[];
}

export default function KitchenTemplate({
    printerId,
    order,
    orderItems,
}: KitchenTemplateProps) {
    const getOrderTypeString = (type: string) => {
        switch (type) {
            case 'dine_in':
                return 'في المطعم';
            case 'takeaway':
                return 'خارجي';
            case 'delivery':
                return 'توصيل';
            case 'companies':
                return 'شركات';
            case 'talabat':
                return 'طلبات';
            case 'web_delivery':
                return 'اونلاين دليفري';
            case 'web_takeaway':
                return 'اونلاين تيك أواي';
            default:
                return type;
        }
    };

    return (
        <div id={'printer_' + printerId} className="w-[500px] font-bold text-2xl space-y-4">
            <p className="text-3xl text-center">Order #{order.order_number}</p>
            <p>نوع الطلب : {getOrderTypeString(order.type)}</p>
            <p>التاريخ : {new Date().toLocaleString('ar-EG', { hour12: true })}</p>
            {order.type === 'dine_in' && order.dine_table_number && (
                <p>طاولة رقم {order.dine_table_number}</p>
            )}
            <table className="w-full table-fixed border-collapse border-solid border border-black">
                <thead>
                    <tr>
                        <th className="p-2 border border-solid border-black">المنتج</th>
                        <th className="p-2 border border-solid border-black">الكمية</th>
                    </tr>
                </thead>
                <tbody>
                    {orderItems.map((item, index) => (
                        <React.Fragment key={index}>
                            <tr>
                                <td className="px-2 py-4 border border-solid border-black">
                                    {item.name}
                                </td>
                                <td className="px-2 py-4 border border-solid border-black">
                                    {item.quantity}
                                </td>
                            </tr>
                            {item.notes && (
                                <tr>
                                    <td colSpan={2} className="px-2 py-4 border border-solid border-black">
                                        ملاحظات : {item.notes}
                                    </td>
                                </tr>
                            )}
                        </React.Fragment>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
