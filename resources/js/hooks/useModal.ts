import { useState } from "react";

const useModal = () => {
    const [open, setOpen] = useState(false);
    const [confirmLoading, setConfirmLoading] = useState(false);

    const showModal = () => {
        setOpen(true);
    };

    const closeModal = () => setOpen(false);

    const onOK = () => {
        setConfirmLoading(true);
        setTimeout(() => {
            setOpen(false);
            setConfirmLoading(false);
        }, 2000);
    };

    const onCancel = () => {
        setOpen(false);
    };

    return {
        open,
        closeModal,
        confirmLoading,
        setConfirmLoading,
        showModal,
        onOK,
        onCancel
    };
};

export default useModal;
