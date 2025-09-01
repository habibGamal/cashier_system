import React from 'react';
import { DineInTab } from './DineInTab';
import type { Order } from '@/types';

interface ReceiveOrdersPaymentsTabProps {
    orders: Order[];
}

export const ReceiveOrdersPaymentsTab: React.FC<ReceiveOrdersPaymentsTabProps> = ({ orders }) => {
    return <DineInTab orders={orders} />;
};
