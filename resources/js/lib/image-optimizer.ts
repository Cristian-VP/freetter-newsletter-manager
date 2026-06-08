import imageCompression from "browser-image-compression";

/**
 * Optimiza una imagen comprimiéndola y redimensionándola en el lado del cliente.
 * Evita la subida de archivos gigantescos al servidor.
 * 
 * @param file El archivo de imagen original (debe ser de tipo File)
 * @param maxSizeMB Tamaño máximo deseado en MB (por defecto 1MB)
 * @param maxWidthOrHeight Dimensión máxima (ancho o alto) en píxeles (por defecto 1200px)
 * @returns El archivo comprimido, o el archivo original si no era imagen o falló la compresión
 */
export async function optimizeImage(
  file: File | null | undefined, 
  maxSizeMB = 1, 
  maxWidthOrHeight = 1200
): Promise<File | null | undefined> {
  // Verificaciones iniciales
  if (!file) return file;
  if (!file.type.startsWith("image/")) return file;

  try {
    const options = {
      maxSizeMB,
      maxWidthOrHeight,
      useWebWorker: true,
      // Conservar el formato webp/jpeg/png si es posible, sino usa el comportamiento por defecto
      alwaysKeepResolution: false,
    };

    // La función devuelve un Blob o File. Asegurarnos que es File.
    const compressedBlob = await imageCompression(file, options);
    
    // Devolvemos un nuevo objeto File manteniendo el nombre original
    return new File([compressedBlob], file.name, {
      type: compressedBlob.type,
      lastModified: Date.now(),
    });
  } catch (error) {
    console.error("Error al comprimir la imagen en cliente:", error);
    // Si la compresión falla (ej. imagen corrupta o navegador no soporta),
    // devolvemos la imagen original para que el backend la gestione.
    return file;
  }
}
