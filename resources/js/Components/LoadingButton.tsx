import useLoading from "@/hooks/useLoading";
import { Button, ButtonProps } from "antd";
import React from "react";

interface LoadingButtonProps extends ButtonProps {
    onCustomClick: (finish: () => void) => void;
    children: React.ReactNode;
}

export default function LoadingButton({
    onCustomClick,
    children,
    ...props
}: LoadingButtonProps) {
    const { loading, finish, start } = useLoading();
    return (
        <Button
            onClick={() => {
                start();
                onCustomClick(finish);
            }}
            loading={loading}
            disabled={props.disabled || loading}
            {...props}
        >
            {children}
        </Button>
    );
}
