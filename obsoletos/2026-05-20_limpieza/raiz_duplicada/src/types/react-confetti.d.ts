declare module 'react-confetti' {
    import { Component } from 'react';

    export interface ConfettiProps {
        width?: number;
        height?: number;
        numberOfPieces?: number;
        recycle?: boolean;
        run?: boolean;
        colors?: string[];
        opacity?: number;
        initialVelocityX?: number;
        initialVelocityY?: number;
        tweenDuration?: number;
        onConfettiComplete?: (confetti?: any) => void;
        drawShape?: (ctx: CanvasRenderingContext2D) => void;
        gravity?: number;
        wind?: number;
        friction?: number;
    }

    export default class Confetti extends Component<ConfettiProps> { }
}
