import React, { useState } from "react";
import { Button, InputNumber, Tag, Typography, Modal, Input } from "antd";
import { usePage } from "@inertiajs/react";
import {
    MinusCircleOutlined,
    PlusCircleOutlined,
    DeleteOutlined,
    EditOutlined,
    DollarOutlined,
    PercentageOutlined,
} from "@ant-design/icons";

import { OrderItemData, OrderItemAction, User, PageProps } from "@/types";
import { formatCurrency } from "@/utils/orderCalculations";
import ItemDiscountModal from "./ItemDiscountModal";

const { TextArea } = Input;

interface OrderItemProps {
    orderItem: OrderItemData;
    dispatch: React.Dispatch<OrderItemAction>;
    disabled?: boolean;
    user: User;
    forWeb?: boolean; // New prop for web orders
}

export default function OrderItem({
    orderItem,
    dispatch,
    disabled,
    user,
    forWeb,
}: OrderItemProps) {
    // Get settings from page props
    const { allowCashierDiscounts, allowCashierItemChanges } = usePage<PageProps>().props;

    const [isNotesModalOpen, setIsNotesModalOpen] = useState(false);
    const [isDiscountModalOpen, setIsDiscountModalOpen] = useState(false);
    const [notes, setNotes] = useState(orderItem.notes || "");

    // Check if user can apply discounts (admin always, cashier if allowed)
    const canApplyDiscount = user.role === 'admin' || allowCashierDiscounts;

    // Check if user can change items (admin always, cashier if allowed)
    const canChangeItems = user.role === 'admin' || allowCashierItemChanges;

    // For web orders, disable quantity changes but allow notes editing
    const quantityDisabled = disabled || forWeb;

    // Calculate item discount
    const itemSubtotal = orderItem.price * orderItem.quantity;
    let itemDiscount = 0;

    if (orderItem.item_discount_type === 'percent' && orderItem.item_discount_percent) {
        itemDiscount = itemSubtotal * (orderItem.item_discount_percent / 100);
    } else {
        itemDiscount = orderItem.item_discount ?? 0;
    }

    // Ensure discount doesn't exceed subtotal
    itemDiscount = Math.min(itemDiscount, itemSubtotal);
    const itemTotal = itemSubtotal - itemDiscount;

    // Check if item has discount applied
    const hasDiscount = itemDiscount > 0;

    const onChangeQuantity = (quantity: number | null) => {
        if (quantity !== null) {
            dispatch({
                type: "changeQuantity",
                id: orderItem.product_id,
                quantity,
                user,
            });
        }
    };

    const onIncrement = () => {
        dispatch({ type: "increment", id: orderItem.product_id, user });
    };

    const onDecrement = () => {
        dispatch({ type: "decrement", id: orderItem.product_id, user });
    };

    const onDelete = () => {
        dispatch({ type: "delete", id: orderItem.product_id, user });
    };

    const onSaveNotes = () => {
        dispatch({
            type: "changeNotes",
            id: orderItem.product_id,
            notes,
            user,
        });
        setIsNotesModalOpen(false);
    };

    const onOpenNotesModal = () => {
        setNotes(orderItem.notes || "");
        setIsNotesModalOpen(true);
    };

    const onOpenDiscountModal = () => {
        setIsDiscountModalOpen(true);
    };

    return (
        <>
            <div className="isolate-3 flex flex-col gap-4 my-4">
                <div className="flex justify-between items-center">
                    <div className="flex flex-col">
                        <Typography.Paragraph className="my-0!">
                            {orderItem.name}
                        </Typography.Paragraph>
                        {forWeb && orderItem.notes && (
                            <Typography.Paragraph
                                className="my-0! text-sm! text-gray-500 ltr"
                                ellipsis={{ rows: 2 }}
                            >
                                {orderItem.notes}
                            </Typography.Paragraph>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button
                            disabled={quantityDisabled}
                            onClick={onDecrement}
                            className="icon-button"
                            icon={<MinusCircleOutlined />}
                            size="small"
                        />
                        <InputNumber
                            disabled={quantityDisabled}
                            min={0.001}
                            step={0.001}
                            precision={3}
                            value={orderItem.quantity}
                            onChange={onChangeQuantity}
                            style={{ width: 80 }}
                        />
                        <Button
                            disabled={quantityDisabled}
                            onClick={onIncrement}
                            className="icon-button"
                            icon={<PlusCircleOutlined />}
                            size="small"
                        />
                    </div>
                </div>
                <div className="flex justify-between items-center">
                    <div className="flex flex-col gap-1">
                        <Typography.Text>
                            السعر :
                            <Tag
                                className="mx-4 text-lg"
                                bordered={false}
                                color={hasDiscount ? "default" : "success"}
                                style={hasDiscount ? { textDecoration: 'line-through' } : {}}
                            >
                                {formatCurrency(itemSubtotal)}
                            </Tag>
                        </Typography.Text>
                        {hasDiscount && (
                            <>
                                <Typography.Text>
                                    الخصم :
                                    <Tag className="mx-4" color="error">
                                        {formatCurrency(itemDiscount)}
                                        {orderItem.item_discount_type === 'percent' &&
                                            ` (${orderItem.item_discount_percent}%)`
                                        }
                                    </Tag>
                                </Typography.Text>
                                <Typography.Text strong>
                                    الإجمالي :
                                    <Tag className="mx-4 text-lg" color="blue">
                                        {formatCurrency(itemTotal)}
                                    </Tag>
                                </Typography.Text>
                            </>
                        )}
                    </div>
                    <div className="flex gap-4">
                        {canApplyDiscount && (
                            <Button
                                disabled={disabled}
                                onClick={onOpenDiscountModal}
                                type={hasDiscount ? "primary" : "default"}
                                className="icon-button"
                                icon={<PercentageOutlined />}
                                size="small"
                                style={hasDiscount ? { backgroundColor: '#ff4d4f', borderColor: '#ff4d4f' } : {}}
                            />
                        )}
                        <Button
                            disabled={quantityDisabled}
                            onClick={onDelete}
                            danger
                            type="primary"
                            className="icon-button"
                            icon={<DeleteOutlined />}
                            size="small"
                        />
                        <Button
                            disabled={disabled} // Notes editing should follow the general disabled state
                            onClick={onOpenNotesModal}
                            type="primary"
                            className="icon-button"
                            icon={<EditOutlined />}
                            size="small"
                        />
                    </div>
                </div>
            </div>

            <Modal
                title="ملاحظات الصنف"
                open={isNotesModalOpen}
                onOk={onSaveNotes}
                onCancel={() => setIsNotesModalOpen(false)}
                okText="حفظ"
                cancelText="إلغاء"
            >
                <TextArea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="اكتب ملاحظات الصنف هنا..."
                    rows={4}
                />
            </Modal>

            <ItemDiscountModal
                open={isDiscountModalOpen}
                onCancel={() => setIsDiscountModalOpen(false)}
                orderItem={orderItem}
                dispatch={dispatch}
                user={user}
            />
        </>
    );
}

