import { useState } from "react";

export interface WeeklyActivityData {
  week: string;
  label: string;
  count: number;
}

interface WeeklyActivityChartProps {
  data: WeeklyActivityData[];
}

const CHART_HEIGHT = 180;
const CHART_WIDTH = 700;
const PADDING = { top: 20, right: 20, bottom: 30, left: 40 };
const LINE_COLOR = "#2563eb";
const AREA_GRADIENT_START = "rgba(37, 99, 235, 0.15)";
const AREA_GRADIENT_END = "rgba(37, 99, 235, 0)";
const DOT_RADIUS = 3.5;
const DOT_STROKE_WIDTH = 2;

export default function WeeklyActivityChart({ data }: WeeklyActivityChartProps) {
  const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);

  if (data.length === 0) {
    return (
      <div className="flex h-48 items-center justify-center text-sm text-zinc-400">
        No activity data yet
      </div>
    );
  }

  const innerWidth = CHART_WIDTH - PADDING.left - PADDING.right;
  const innerHeight = CHART_HEIGHT - PADDING.top - PADDING.bottom;

  const maxCount = Math.max(...data.map((d) => d.count), 1);

  // Calculate point positions
  const points = data.map((d, i) => {
    const x = PADDING.left + (i / (data.length - 1 || 1)) * innerWidth;
    const y = PADDING.top + innerHeight - (d.count / maxCount) * innerHeight;
    return { x, y, ...d };
  });

  // Build smooth path using cubic bezier curves
  const linePath = points
    .map((point, i) => {
      if (i === 0) {
        return `M ${point.x},${point.y}`;
      }
      const prev = points[i - 1];
      const cpx1 = prev.x + (point.x - prev.x) / 3;
      const cpx2 = point.x - (point.x - prev.x) / 3;
      return `C ${cpx1},${prev.y} ${cpx2},${point.y} ${point.x},${point.y}`;
    })
    .join(" ");

  // Build area path (line + bottom edge)
  const areaPath = `${linePath} L ${points[points.length - 1].x},${PADDING.top + innerHeight} L ${points[0].x},${PADDING.top + innerHeight} Z`;

  // Y-axis labels
  const yTicks = [0, Math.ceil(maxCount / 2), maxCount];

  // X-axis labels (show every other label on mobile, all on desktop)
  const xLabels = points.map((p, i) => ({
    x: p.x,
    label: p.label,
    show: data.length <= 6 || i % 2 === 0,
  }));

  return (
    <div className="w-full overflow-x-auto">
      <svg
        viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`}
        className="min-w-[500px] w-full"
        preserveAspectRatio="xMidYMid meet"
      >
        <defs>
          <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stopColor={AREA_GRADIENT_START} />
            <stop offset="100%" stopColor={AREA_GRADIENT_END} />
          </linearGradient>
        </defs>

        {/* Grid lines */}
        {yTicks.map((tick, i) => {
          const y = PADDING.top + innerHeight - (tick / maxCount) * innerHeight;
          return (
            <g key={i}>
              <line
                x1={PADDING.left}
                y1={y}
                x2={CHART_WIDTH - PADDING.right}
                y2={y}
                stroke="#f4f4f5"
                strokeWidth="1"
              />
              <text
                x={PADDING.left - 8}
                y={y + 4}
                textAnchor="end"
                fill="#a1a1aa"
                fontSize="10"
                fontFamily="Satoshi, sans-serif"
              >
                {tick}
              </text>
            </g>
          );
        })}

        {/* Area fill */}
        <path d={areaPath} fill="url(#areaGradient)" />

        {/* Line */}
        <path
          d={linePath}
          fill="none"
          stroke={LINE_COLOR}
          strokeWidth="2"
          strokeLinecap="round"
          strokeLinejoin="round"
        />

        {/* Data points */}
        {points.map((point, i) => (
          <g
            key={i}
            onMouseEnter={() => setHoveredIndex(i)}
            onMouseLeave={() => setHoveredIndex(null)}
            className="cursor-pointer"
          >
            {/* Invisible larger hit area */}
            <circle
              cx={point.x}
              cy={point.y}
              r={12}
              fill="transparent"
            />
            {/* Visible dot */}
            <circle
              cx={point.x}
              cy={point.y}
              r={hoveredIndex === i ? 5 : DOT_RADIUS}
              fill="#fff"
              stroke={LINE_COLOR}
              strokeWidth={DOT_STROKE_WIDTH}
              className="transition-all duration-150"
            />
            {/* Tooltip */}
            {hoveredIndex === i && (
              <g>
                <rect
                  x={point.x - 40}
                  y={point.y - 32}
                  width={80}
                  height={24}
                  rx={6}
                  fill="#18181b"
                />
                <text
                  x={point.x}
                  y={point.y - 16}
                  textAnchor="middle"
                  fill="#fff"
                  fontSize="11"
                  fontWeight="500"
                  fontFamily="Satoshi, sans-serif"
                >
                  {point.label} · {point.count}
                </text>
              </g>
            )}
          </g>
        ))}

        {/* X-axis labels */}
        {xLabels.map((label, i) =>
          label.show ? (
            <text
              key={i}
              x={label.x}
              y={CHART_HEIGHT - 8}
              textAnchor="middle"
              fill="#a1a1aa"
              fontSize="10"
              fontFamily="Satoshi, sans-serif"
            >
              {label.label}
            </text>
          ) : null,
        )}
      </svg>
    </div>
  );
}
