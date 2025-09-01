import { ReactNode } from "react";

export default function ShiftSummaryTemplate({
  shiftNumber,
  info,
}: {
  shiftNumber: number;
  info: { title: ReactNode; value: string }[];
}) {
  return (
    <div id="receipt" className="w-[500px] font-bold text-xl">
      <p className="text-5xl text-center">Shift #{shiftNumber}</p>
      <table className="w-full table-fixed border-collapse border-solid border border-black">
        <tbody>
          {info.map((item, index) => (
            <tr key={index}>
              <td className="px-2 py-4 border border-solid border-black">{item.title}</td>
              <td className="px-2 border border-solid border-black">{item.value}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <p className="text-center"> {new Date().toLocaleString('ar-EG', { hour12: true })}</p>
    </div>
  );
}
