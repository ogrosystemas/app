export async function compressImage(
  file,
  quality = 0.7,
  maxWidth = 1280
) {

  return new Promise((resolve) => {

    const reader =
      new FileReader();

    reader.readAsDataURL(file);

    reader.onload = (event) => {

      const img =
        new Image();

      img.src =
        event.target.result;

      img.onload = () => {

        const canvas =
          document.createElement(
            'canvas'
          );

        let width =
          img.width;

        let height =
          img.height;

        if (width > maxWidth) {

          height *=
            maxWidth / width;

          width = maxWidth;

        }

        canvas.width = width;

        canvas.height = height;

        const ctx =
          canvas.getContext('2d');

        ctx.drawImage(
          img,
          0,
          0,
          width,
          height
        );

        resolve(

          canvas.toDataURL(
            'image/jpeg',
            quality
          )

        );

      };

    };

  });

}