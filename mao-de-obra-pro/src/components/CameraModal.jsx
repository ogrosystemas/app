import { useRef, useState } from 'react';
import { Camera, X, Check } from 'lucide-react';

export const CameraModal = ({ onCapture, onClose }) => {
  const videoRef = useRef(null);
  const [stream, setStream] = useState(null);
  const [photo, setPhoto] = useState(null);

  const startCamera = async () => {
    const mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' },
      audio: false
    });
    setStream(mediaStream);
    videoRef.current.srcObject = mediaStream;
  };

  const capture = () => {
    const canvas = document.createElement('canvas');
    canvas.width = videoRef.current.videoWidth;
    canvas.height = videoRef.current.videoHeight;
    canvas.getContext('2d').drawImage(videoRef.current, 0, 0);
    setPhoto(canvas.toDataURL('image/jpeg', 0.7)); // Comprime para 70%
    stream.getTracks().forEach(track => track.stop());
  };

  if (!stream && !photo) startCamera();

  return (
    <div className="fixed inset-0 bg-black z-50 flex flex-col items-center justify-center p-4">
      {!photo ? (
        <>
          <video ref={videoRef} autoPlay playsInline className="w-full rounded-lg" />
          <button onClick={capture} className="mt-8 bg-white p-6 rounded-full text-blue-600">
            <Camera size={32} />
          </button>
        </>
      ) : (
        <>
          <img src={photo} className="w-full rounded-lg" alt="Captura" />
          <div className="flex gap-4 mt-8">
            <button onClick={() => setPhoto(null)} className="bg-red-500 text-white p-4 rounded-full">
              <X size={24} />
            </button>
            <button onClick={() => onCapture(photo)} className="bg-green-500 text-white p-4 rounded-full">
              <Check size={24} />
            </button>
          </div>
        </>
      )}
    </div>
  );
};