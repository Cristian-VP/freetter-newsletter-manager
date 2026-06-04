import type { Editor } from "@tiptap/react"
import { Plugin, PluginKey } from "@tiptap/pm/state"

export type UploadFunction = (
  file: File,
  onProgress?: (event: { progress: number }) => void,
  abortSignal?: AbortSignal
) => Promise<string>

export function createImagePastePlugin(uploadFn: UploadFunction): Plugin {
  return new Plugin({
    key: new PluginKey("imagePaste"),
    props: {
      handlePaste(view, event) {
        const clipboardData = event.clipboardData

        if (!clipboardData || clipboardData.files.length === 0) {
          return false
        }

        const imageFiles = Array.from(clipboardData.files).filter((file) =>
          file.type.startsWith("image/")
        )

        if (imageFiles.length === 0) {
          return false
        }

        event.preventDefault()

        // Access the Tiptap editor instance from the view
        const editor = (view as unknown as { editor: Editor }).editor

        if (!editor) {
          return false
        }

        imageFiles.forEach((file) => {
          uploadFn(file)
            .then((url) => {
              editor.commands.insertContent({
                type: "image",
                attrs: { src: url },
              })
            })
            .catch((error: Error) => {
              console.error("Image paste upload failed:", error)
            })
        })

        return true
      },
    },
  })
}
