import React, { useState, useRef } from 'react';
import { Camera, X, Check, RotateCcw } from 'lucide-react';

const CameraModal = ({ isOpen, onClose, onCapture }) => {
  const [stream, setStream] = useState(null);
  const [photo, setPhoto] = useState(null);
  const videoRef = useRef(null);
  const canvasRef = useRef(null);

  const startCamera = async () => {
    try {
      const mediaStream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      });
      setStream(mediaStream);
      if (videoRef.current) {
        videoRef.current.srcObject = mediaStream;
      }
    } catch (error) {
      console.error('Error accessing camera:', error);
      alert('Não foi possível acessar a câmera');
    }
  };

  const stopCamera = () => {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      setStream(null);
    }
  };

  const takePhoto = () => {
    if (videoRef.current && canvasRef.current) {
      const video = videoRef.current;
      const canvas = canvasRef.current;
      const context = canvas.getContext('2d');

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      context.drawImage(video, 0, 0, canvas.width, canvas.height);

      const photoData = canvas.toDataURL('image/jpeg', 0.8);
      setPhoto(photoData);
      stopCamera();
    }
  };

  const retakePhoto = () => {
    setPhoto(null);
    startCamera();
  };

  const confirmPhoto = () => {
    if (photo) {
      onCapture(photo);
      handleClose();
    }
  };

  const handleClose = () => {
    stopCamera();
    setPhoto(null);
    onClose();
  };

  React.useEffect(() => {
    if (isOpen) {
      startCamera();
    } else {
      stopCamera();
    }
    return () => stopCamera();
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 bg-black bg-opacity-95 flex items-center justify-center animate-fade-in">
      <div className="relative w-full h-full max-w-lg flex flex-col">
        {/* Header */}
        <div className="flex justify-between items-center p-4 bg-black text-white">
          <h3 className="text-lg font-semibold">Tirar Foto</h3>
          <button onClick={handleClose} className="p-2 hover:bg-white hover:bg-opacity-10 rounded-lg">
            <X size={24} />
          </button>
        </div>

        {/* Camera or Photo Preview */}
        <div className="flex-1 flex items-center justify-center bg-black">
          {!photo ? (
            <video
              ref={videoRef}
              autoPlay
              playsInline
              className="w-full h-full object-cover"
            />
          ) : (
            <img src={photo} alt="Preview" className="w-full h-full object-contain" />
          )}
        </div>

        {/* Hidden Canvas */}
        <canvas ref={canvasRef} className="hidden" />

        {/* Controls */}
        <div className="p-4 bg-black">
          {!photo ? (
            <button
              onClick={takePhoto}
              className="w-full bg-white text-black py-4 rounded-full font-semibold flex items-center justify-center gap-2 hover:bg-gray-100 transition-colors"
            >
              <Camera size={24} />
              Capturar
            </button>
          ) : (
            <div className="flex gap-4">
              <button
                onClick={retakePhoto}
                className="flex-1 bg-gray-700 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2 hover:bg-gray-600 transition-colors"
              >
                <RotateCcw size={20} />
                Refazer
              </button>
              <button
                onClick={confirmPhoto}
                className="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold flex items-center justify-center gap-2 hover:bg-green-700 transition-colors"
              >
                <Check size={20} />
                Usar Foto
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default CameraModal;