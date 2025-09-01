import { useState } from "react";

const useLoading = () => {
    const [loading, setLoading] = useState(false);
    const start = () => {
        setLoading(true);
    };
    const finish = () => {
        setLoading(false);
    };
    return {
        loading,
        start,
        finish,
    };
};

export default useLoading;
