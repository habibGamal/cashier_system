import React, { useState } from "react";
import { Button, InputNumber, Tag, Typography, Modal, Input } from "antd";
import {
    MinusCircleOutlined,
    PlusCircleOutlined,
    DeleteOutlined,
    EditOutlined,
} from "@ant-design/icons";

import { OrderItemData, OrderItemAction, User } from "@/types";
import { formatCurrency } from "@/utils/orderCalculations";

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
    const [isNotesModalOpen, setIsNotesModalOpen] = useState(false);
    const [notes, setNotes] = useState(orderItem.notes || "");

    // For web orders, disable quantity changes but allow notes editing
    const quantityDisabled = disabled || forWeb;

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
                    <Typography.Text>
                        السعر :
                        <Tag
                            className="mx-4 text-lg"
                            bordered={false}
                            color="success"
                        >
                            {formatCurrency(
                                orderItem.price * orderItem.quantity
                            )}
                        </Tag>
                    </Typography.Text>
                    <div className="flex gap-4">
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
        </>
    );
}
