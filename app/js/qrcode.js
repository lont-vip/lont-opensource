let httpurl = window.location.href;
// qrcode($('.qcode'),300,'');
qrcode($('.qcode'),300,httpurl);
/**
 * @param  {[type]} qrCodeDiv   [description]
 * @param  {[type]} size        [description]
 * @param  {[type]} text        [description]
 * @return {[type]}             [description]
 */
function qrcode(qrCodeDiv,size,text)
{
    var option = {
          render:'image',
          minVersion:1,
          maxVersion:40,
          ecLevel: 'L',
          minVersion:5,
          left:0,
          top:0,
          size:size,
          fill:'#000',
          background:'#fff',
          text: text,
          radius:0,
          quiet:0,
          mode:4,
          mSize:0.15,
          mPosX:0.5,
          mPosY:0.5,
          image:null
        }

    $(qrCodeDiv).qrcode(option);
}