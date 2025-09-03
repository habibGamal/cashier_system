import React from 'react';
import { DisplayTab } from './DisplayTab';
import type { Order } from '@/types';

interface ReceiveOrdersPaymentsTabProps {
    orders: Order[];
}

export const ReceiveOrdersPaymentsTab: React.FC<ReceiveOrdersPaymentsTabProps> = ({ orders }) => {
    return <DisplayTab orders={orders} />;
};
