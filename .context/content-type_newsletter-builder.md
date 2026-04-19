# GHOST 
De ghost me gusta su forma de dividir las publicaciones por: programadas, borradores y publicadas. Voy a copiar la opción de navegacion de Crear. Esta en la barra de navegación

De la vista en desktop tiene side nav que aplicaremos pero no usaremos su contenido, no me gusta a execepción del icono de crear que es un Lapiz y con una linea como de escritura. 

En la vista movil la importante lo mejor que tiene es que puedes crear contenido desde el movil! Eso si que es muy importante. 

Cuando accede a crear contenido entra en la vista de Post (ellos lo llaman asi) y esta filtrado por todos. Carga el listado del contenido creado. 

De Ghost vamos a copiar la sección de newsletter builder y la forma de organizar las publicaciónes, en mi caso newsletter. Esto es al entrar en crear, carga la lista y tu decides si accedes a una en borrador, programada (verificar si mi backend soporta esto), publicada o crear una. Si se accedee a una nueva se incia de cero y entramos en el newsletter builder y para el resto de opciones accederemos igual pero cargando el contenido de la newsletter.  

Cuando sales de una newsletter sin dar a enviar o progrmar se considera como draft 

# Substack
De substack voy a copiar los tres opciones (Home, subscriptores, panel administracion "Dashboard") principales a excepcion de algunas que lo haré al estilo instagram y el newsletter builder de ghost y como organiza el espacio de creación de contenido. También voy a copiar como se ve la creación de post para mi proyecto y para substack "Notes".


- Navegación: 

        Home: Cuando se accede se accede a home que es la lista de publicaciones (posts en  mi caso) de otros subscriptores y de los que tu estas subscrito. Esta vista solo carga Post, que no quita que estos incluyan un enlace para redirigir a una publicación (newletter). Esto ya lo tengo cuando un user se registr accede a Home. (Icono honme). Esta en la barra de navegación.

        Subcripciones: muestra una lista de solo las newsletter que tu estas subcrita, la lista solo incluye -> Autor, fecha, Titulo subtitulo, tiempo lectura, me gusta y una pequeña imagen (la imagen no la incluyo), ademas de los tipicos tres puntitos para: copiar enlace, compartir y cancelar suscripcion (importante). Esta en la barra de navegación.

        Dashboard: Lo divide por Contenido, audiencia (data analisis, estadistica), herramientas creadores. Yo creo que para MVP nos basta con COntenido y audiencia, por que en contenido verás si se han subscrito desde ese, me gustas, vistas, y porcentaje de abiertos. Es importante que el dashboard no va aestar en la barra de navegación, lo especifico en la seccion de Instagram  

        ![Contenido](image-1.png)  

        ![Audiencia - estadistica](image-2.png)  

        ![Donaciones o configurar donación](image-3.png)  

        Post: Cuando se pulso en el boton de + (opcion que copio de Instagram) se creará un post. El post solo va a tener Titulo, subtitulo (opcional) Imagen (opcional), galeria (valorar viabilidad - opcional ), cuerpo de texto y links (opcional). AL ser un post debe tener un limite de palabras no se cuantas. Podrías averiguar cual es la media o o lo que usa Substack o Mastodom
        ![Modal Note Subtack Desktop view](image-4.png)

# Instagram
Me gusta como distribuye las cosas en el espacio y como esta definido el diseño en movil que es nuestra prioridad. 

De aquí voy a copiar: 

- En el Header en movil el botón +, icono instagram, para crear un Post (publicaciones que se verán en Home), esto abrirá el creador de Post que he indicado en la seccion de Subtack, est aopcion esta . A la derecha del header quiero menu burguer de dos barritas y en el las opciones de: configuracion, dashboar (aqui accederá al panel admin analisis, etc. Más adelante cuando implementemos roles podremos añadir aqui las opciones de creacion de roles o invitar, etc), Sign out. La imagen que te incluyo es del desktop pero asi puedes acerte una idea, en vista movil lo que haremos será lo mismo como un modal o un side nav que mostrará estas opciones. 
![alt text](image-5.png)  

- Barra de navegación bottom nav bar en movil y side nav en desktop. Su contenido es y posibilidades de navegación: Home (Icono home Substack), Subcripciones (Icono Subscriptores Substack), Crear Newsletter(Icono de lapiz de Ghost que mostrará la publicaciones en borrador, progrmadas y publicadas) y Perfil (Circulito de imagen de perfil). 

Aclaración de Perfil: Cuando accedemos a esta opcion veremos como en instagram nuestro perfil, empezando con la cabecera con foto de perfil, nopmbre usuario publico, numero de publicaciones (tanto post como newsletters), nº seguidores y nº de seguidos. Luego abrá una navegacion tipo slide, para poder ver las publicaciones de newseletters y las publicaciones post. Sería como hechar un vistacito a nuestro perfil como en instagram y claro esta si pulsamos sobre una nos llevará a ella.  
![Newsletter - perfil](image-6.png)  
En esta captura solo ves imagenes pero lo suyo sería dividir en rows con una imagen peque en un lado y el titulo. Solo me gusta como organiza. Mejor seria al estilo substack
![Publicaciones perfil](image-7.png)  
Para la opción de Post sería lo mismo solo que seleccionando el otro icono. 
 

# GHOST Newsletter Builder cosas a copiar
## Esta compuesto por tres bloques:
### 1 Add frature image (dispositivo o unsplash)
Se carga una imagen en la cabecera
### Titulo
El titulo solo acepta texto
### Cuerpo de newletter

Elementos en ghost (cuando pulsas sobre el botón + en el cuerpo la news):  
- Image (MVP)
- Divider (MVP)
- Button (MVP)
- Bookmark (Como un link a otras publicaciones tuyas o lo que quieras)
- Gallery
- Public preview (si solo es visible en web o mail)
- Call to action  (para publicitar esponsors) (mis esponsors serán gente que dona money) (MVP V2)
- Email content (Solo visible en mail) 
- Callout (Recuadro como un post it de una idea con icono)
- Signup (Recuadro Con titulo, sub y un input que el botón esta denbtro del inputl. Solo es visible para gente que no es miembro) (MVP)
- Header (como un banner con titulo y subtitulo) (MVP)
- Toggle
- Video
- Audio
- File
- GIF (MVP - Valorar a usar https://developers.giphy.com/)
- HTML ¿?
- mARKDOWN ?¿
- Embebed (MVP V2 Youtube ?¿ )

#### Dentro del cuerpo 
- Cuano añadimos un elemento se nos abre un recuadro que es un dialog para modifcar el elemento:  
        - Image: layaout (medio, ancho y pantalla completo), link enlace -> No ofrece la opcion de eliminar
        - Divider: sin opciones de configuracion, solo es un linea horizontal
        - Button: content aligment (izq o centrado) , testo boton y url. Dentro de un recuadro se alinea
        - Sign in up: Layaout (medio, ancho y pantalla completo), alineación, color fondo, color botón, texto botón
        - Header: Layaout (medio, ancho y pantalla completo), alineacion, color fondo, toogle añadir botón
        - GIF: Aparece un buscador, com una mini galeria, al igual que si eljes la opción de añadir una imagen en la cabecera con unsplash. Cuando se añade aparece la opción de linkear un enlace. 





