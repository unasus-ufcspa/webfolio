/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var ultimoComentario = 0;
var abriu = 0;
var comentou = 0;
var contArraysMult = 0;
function procuraBolinha() {
    ed = tinyMCE.activeEditor;
    var span = tinyMCE.activeEditor.dom.select('span');
    for (var j = 0; j < span.length; j++) {
        console.debug(span[0]);
        if (span[j].className == "bolinhaFolio") {
            //console.log("procurando bolinhas");
            position = ed.dom.getPos(span);
            console.log(position.y);
            var div = document.createElement("DIV");
            div.setAttribute("id", span[j].id);

            var novoDiv = document.createElement("DIV");
            var especif = document.getElementsByClassName("comentarioEspec")[0];
            novoDiv.setAttribute("id", span[j].id);
            novoDiv.setAttribute("class", "caixaComentEsp");
            especif.appendChild(novoDiv);

            div.setAttribute("class", "bolinha");
            div.setAttribute("onClick", "abre(" + span[j].id + ")");

            var para = document.createElement("p");
            var node = document.createTextNode(span[j].id);

            para.appendChild(node);
            div.appendChild(para);
            //document.body.appendChild(div);
            document.getElementById("bolinhas").appendChild(div);
            ed = tinyMCE.activeEditor;
            position = ed.dom.getPos(span[j]);
            tinyMCE.DOM.setStyle(div, "top", (position.y + 80));
        }
    }

    agrupaBolinhas();
}

function agrupaBolinhas() {
    var r;
    var q;
    var bolinhas = document.getElementsByClassName('bolinha');
    if (bolinhas.length > 1) {
        for (r = 0; r < bolinhas.length - 1; r++) {
            for (q = 1; q < bolinhas.length; q++) {
                var bolinhaA = bolinhas[r];
                var bolinhaB = bolinhas[q];
                if (bolinhaA.id !== bolinhaB.id) {
                    agrupaBolinhasMult();
                    var posicaoA = bolinhaA.getBoundingClientRect();
                    var posicaoB = bolinhaB.getBoundingClientRect();
                    if (posicaoA.top === posicaoB.top && posicaoA.left === posicaoB.left) {
                        bolinhaA.style.visibility = "hidden";
                        bolinhaB.style.visibility = "hidden";
                        bolinhaA.style.left = "19%";
                        bolinhaB.style.left = "19%";
                        bolinhaA.style.top = (posicaoA.top - 25);
                        bolinhaB.style.top = (posicaoB.top + 25);
                        var multDiv = document.createElement("DIV");
                        multDiv.setAttribute("class", "bolinhaMult");
                        multDiv.setAttribute("id", contArraysMult);
                        multDiv.style.cursor = "pointer";
                        multDiv.style.fontSize = "medium";
                        multDiv.setAttribute("onClick", "expande(" + contArraysMult + ")");
                        var multPara = document.createElement("p");
                        var multNode = document.createTextNode("...");
                        multPara.appendChild(multNode);
                        multDiv.appendChild(multPara);
                        document.getElementById("bolinhas").appendChild(multDiv);
                        tinyMCE.DOM.setStyle(multDiv, "top", posicaoA.top);
                        var idsMult = [bolinhaA.id, bolinhaB.id];
                        arrayArrays.push(idsMult);
                        posicaoM = multDiv.getBoundingClientRect();
                        contArraysMult++;
                    }
                }
            }
        }
        agrupaBolinhasMult();
    }
}

function readNoticeActivity(ativ, caminho) {
    var dataString = {
        "atividade": ativ
    };
    console.log("read notice" + JSON.stringify(dataString));
    $.ajax({
        type: 'post',
        data: JSON.stringify(dataString),
        contentType: 'application/json',
        dataType: 'json',
        url: "" + caminho + "readNoticeActivity",
        success: function (msg) {
        }
    });
}

function agrupaBolinhasMult() {
    if (contArraysMult > 0) {
        var r;
        var q;
        var bolinhas = document.getElementsByClassName('bolinha');
        var bolinhasMult = document.getElementsByClassName('bolinhaMult');
        for (r = 0; r < bolinhasMult.length; r++) {
            for (q = 0; q < bolinhas.length; q++) {
                var bolinhaM = bolinhasMult[r];
                var bolinhaN = bolinhas[q];
                var posicaoM = bolinhaM.getBoundingClientRect();
                var posicaoN = bolinhaN.getBoundingClientRect();
                if (posicaoM.top === posicaoN.top && posicaoM.left === posicaoN.left) {
                    var idMult = bolinhaM.id;
                    bolinhaN.style.visibility = "hidden";
                    bolinhaN.style.left = "19%";
                    var lastB = arrayArrays[idMult].length - 1;
                    for (var l = 0; l < bolinhas.length; l++) {
                        if (bolinhas[l].id === arrayArrays[idMult][lastB]) {
                            var novaBolinhaM = bolinhas[l];
                        }
                    }
                    var posBolinhaM = novaBolinhaM.getBoundingClientRect();
                    var novaPosicaoN = posBolinhaM.top;
                    bolinhaN.style.top = (novaPosicaoN + 50);
                    arrayArrays[idMult].splice(arrayArrays[idMult].length, 0, bolinhaN.id);
                }
            }
        }
    }
}

function expande(id) {
    var bolinhas = document.getElementsByClassName('bolinha');
    for (var i = 0; i < arrayArrays.length; i++) {
        fecharBolinha(i);
    }
    for (i = 0; i < arrayArrays[id].length; i++) {
        for (k = 0; k < bolinhas.length; k++) {
            if (bolinhas[k].id === arrayArrays[id][i] && bolinhas[k].style.visibility == "hidden") {
                bolinhas[k].style.visibility = "visible";
            }
        }
    }
    var mult = document.getElementsByClassName('bolinhaMult');
    for (i = 0; i < mult.length; i++) {
        if (mult[i].id == id) {
            mult[i].style.backgroundColor = "white";
            mult[i].style.color = "#70e7d0";
            mult[i].style.borderStyle = "solid";
            mult[i].style.borderColor = "#70e7d0";
            mult[i].style.borderWidth = "1px";
            mult[i].setAttribute("onClick", "fecharBolinha(" + id + ")");
        }
    }
}
function fecharBolinha(id) {
    var bolinhas = document.getElementsByClassName('bolinha');
    for (var i = 0; i < arrayArrays[id].length; i++) {
        for (k = 0; k < bolinhas.length; k++) {
            if (bolinhas[k].id === arrayArrays[id][i] && bolinhas[k].style.visibility == "visible") {
                bolinhas[k].style.visibility = "hidden";
            }
        }
    }
    var mult = document.getElementsByClassName('bolinhaMult');
    for (i = 0; i < mult.length; i++) {
        if (mult[i].id == id) {
            mult[i].style.backgroundColor = "#70e7d0";
            mult[i].style.color = "white";
            mult[i].setAttribute("onClick", "expande(" + id + ")");
        }
    }
}

function mudaPosicao(id) {
    var bolinhas = document.getElementsByClassName("bolinha");
    for (var t = 0; t < bolinhas.length; t++) {
        if (bolinhas[t].id === id) {
            var bolinha = bolinhas[t];
        }
    }
    bolinha.style.left = "18%";
    ed = tinyMCE.activeEditor;
    var span = tinyMCE.activeEditor.dom.select('span');
    for (var j = 0; j < span.length; j++) {
        if (span[j].id === id) {
            position = ed.dom.getPos(span[j]);
            bolinha.style.top = (position.y + 70);
        }
    }
}
function sidebarBolinhas() {
    var bolinhas = document.getElementsByClassName('bolinha');
    var sidebarEspec = document.getElementsByClassName("comentarioEspec");
    console.log("sidebar bolinhas");
    console.debug(sidebarEspec);
    for (var k = 0; k < bolinhas.length && k < sidebarEspec.length; k++) { //testar com mais de uma bolinha
        console.debug(bolinhas[k]);
        var clone = bolinhas[k].cloneNode(true);
        clone.style.top = null;
        clone.style.left = null;
        clone.style.visibility = "visible";
        clone.className = "bolinhaLateral";
        // console.debug(clone);
        var divBol = document.createElement("div");
        divBol.appendChild(clone);
        sidebarEspec[0].appendChild(divBol);
    }
}

function Observacao(numComVersion) {
    console.log("\n observacao" + numComVersion);
    tinyMCE.activeEditor.getBody().setAttribute('contenteditable', true);
    var existe = 0;
    var x = document.getElementsByClassName('bolinha');
    var span = tinyMCE.activeEditor.selection.getNode();
    position = ed.dom.getPos(span);
    for (i = 0; i < x.length; i++) {
        console.log(x[i].style.top);
        if (x[i].style.top === (position.y + 81) + "px") {
            console.log("hey");

            tinymce.activeEditor.selection.setContent("<span class='bolinhaFolio' style='background-color:#d9fce6' id=" + numComVersion + ">" + tinyMCE.activeEditor.selection.getContent() + "</span>");
            var div = document.createElement("DIV");
            div.setAttribute("class", "bolinhaTemporaria");
            div.setAttribute("id", numComVersion);
            div.setAttribute("id", "temporaria");
            //  div.setAttribute("onClick","testando()" );
            div.setAttribute("onClick", "provisoria(" + numComVersion + ")");
            var para = document.createElement("p");
            var node = document.createTextNode("+");
            para.setAttribute("id", "pTemporario");
            para.appendChild(node);
            div.appendChild(para);
            //document.body.appendChild(div);
            var span = tinyMCE.activeEditor.selection.getNode();
            ed = tinyMCE.activeEditor;
            position = ed.dom.getPos(span);
            tinyMCE.DOM.setStyle(div, "top", 55);
            var editor = document.getElementById("editor");
            tinyMCE.DOM.setStyle(div, "margin-bottom", 5);
            tinyMCE.DOM.setStyle(div, "cursor", "pointer");
            var bolinhas = document.getElementById("bolinhas").appendChild(div);
        }
    }

    if (existe === 0) {
        if (tinyMCE.activeEditor.dom.select('span#' + numComVersion)) {
            var observacaoTemp = tinyMCE.activeEditor.dom.select('span#' + numComVersion);
            console.log(observacaoTemp);
            tinyMCE.activeEditor.dom.setAttrib(observacaoTemp, 'style', '');
            //console.debug(tinymce.activeEditor.sele.getContent({format: 'text'}));
            //  observacaoTemp.setContent(""+$(observacaoTemp.getNode()).unwrap().html()+"");
            tinyMCE.activeEditor.dom.remove(tinyMCE.activeEditor.dom.select('span#' + numComVersion), " ");
            tinyMCE.activeEditor.dom.setAttrib(observacaoTemp, 'id', '');
            //nao funciona totalmente tem que apagar o span e o id tb
            $("div#temporaria.bolinhaTemporaria").remove();
            console.log("tinha uma bolinha antes");
        }

        tinymce.activeEditor.selection.setContent("<span class='bolinhaFolio' style='background-color:#d9fce6' id=" + numComVersion + ">" + tinyMCE.activeEditor.selection.getContent() + "</span>");
        var div = document.createElement("DIV");
        div.setAttribute("class", "bolinhaTemporaria");
        div.setAttribute("id", numComVersion);
        div.setAttribute("id", "temporaria");
        //  div.setAttribute("onClick","testando()" );
        div.setAttribute("onClick", "provisoria(" + numComVersion + ")");
        var para = document.createElement("p");
        var node = document.createTextNode("+");
        para.setAttribute("id", "pTemporario");
        para.appendChild(node);
        div.appendChild(para);
        //document.body.appendChild(div);
        var span = tinyMCE.activeEditor.selection.getNode();
        ed = tinyMCE.activeEditor;
        position = ed.dom.getPos(span);
        tinyMCE.DOM.setStyle(div, "top", 55);
        var editor = document.getElementById("editor");
        tinyMCE.DOM.setStyle(div, "margin-bottom", 5);
        tinyMCE.DOM.setStyle(div, "cursor", "pointer");
        var bolinhas = document.getElementById("bolinhas").appendChild(div);
        //document.getElementByClassName("pusher").appenchild(bolinhas);
    }
}
function carregaComGeral(idActivity, idUser, caminho) {
    var idActivityStudent = idActivity;
    var dataString = 'idActivityStudent=' + idActivityStudent;
    var dataString = {
        idActivityStudent: idActivityStudent,
        ultimoComentario: ultimoComentario
    };
    var idUser = idUser;
    $.ajax({
        type: 'post',
        data: JSON.stringify(dataString),
        contentType: 'application/json',
        dataType: 'json',
        url: "" + caminho + "carregaComGeral",
        success: function (msg) {
            $(".ui.active.centered.loader").removeClass('ui active centered loader');
            $(".ui.active.centered.loader").addClass('ui disable centered loader');
            for (i = 0; i < msg.length; i++) {
                for (teste in msg[i]) {
                    var dataSplit = teste.split("-");
                    var novaData = dataSplit[2] + "/" + dataSplit[1] + "/" + dataSplit[0];

                    var divDatas = document.getElementsByClassName("dataConversaGeral");
                    var flagData = true;
                    for (var g = 0; g < divDatas.length; g++) {
                        if (divDatas[g].textContent === novaData) {
                            flagData = false;
                        }
                    }
                    var horaSplit = msg[i][teste].dt_send.split(" ");
                    var horaMinuto = horaSplit[1].split(":");
                    var novoHorario = horaMinuto[0] + ":" + horaMinuto[1];

                    if (flagData === true) {
                        $(".comentarioGeral").append("<div class='dataConversaGeral' id='commentGeral" + msg[i][teste].id_comment + "'>" + novaData + "</div>");
                    }
                    //cria um div com essa data se ja nao existir um div com essa data

                    if (msg[i][teste].id_author != idUser) {
                        if (msg[i][teste].anexo==null){
                          $(".comentarioGeral").append("<div class='caixaComentarioUsuarioGeral' id='commentGeral" + msg[i][teste].id_comment + "'>"+ msg[i][teste].nm_user+": " + msg[i][teste].tx_comment + "<div class='horarioConversa'>"+novoHorario+"</div></div>");
                        }else{
                           $(".comentarioGeral").append("<div class='caixaComentarioUsuarioAnexo' id='commentGeral" + msg[i][teste].id_comment +
                                   "'>"+ msg[i][teste].nm_user+": " +"<img src='/webfolio/assets/anexoImagem.png' id='anexoCaixa'/><a target='_blank' href='/webfolio/uploads/"+msg[i][teste].anexo.nm_system+"'>"+msg[i][teste].anexo.nm_file+"</a><div class='horarioConversa'>"+novoHorario+"</div></div>");
                        }
                        ultimoComentario = msg[i][teste].id_comment;
                    } else {
                         if (msg[i][teste].anexo==null){
                          $(".comentarioGeral").append("<div class='caixaComentarioGeral' id='commentGeral" + msg[i][teste].id_comment + "'>" + msg[i][teste].tx_comment + "<div class='horarioConversaUsuario'>"+novoHorario+"</div></div><div class='setaConversaUsuario'>");
                          ultimoComentario = msg[i][teste].id_comment;
                        }else{
                             $(".comentarioGeral").append("<div class='caixaComentarioAnexo' id='commentGeral" + msg[i][teste].id_comment
                                     + "'><img src='/webfolio/assets/anexoImagem.png' id='anexoCaixa'/><a target='_blank' href='/webfolio/uploads/"+msg[i][teste].anexo.nm_system+"'>"+msg[i][teste].anexo.nm_file
                                     +"</a><div class='horarioConversaUsuario'>"+novoHorario+"</div></div>");
                            ultimoComentario = msg[i][teste].id_comment;
                        }
                    }
                }
            }
        }
    });
}


function abreBarra(flagConvidado) {

    if (flagConvidado==true){
        document.getElementById('form_txComment').disabled = true;
        document.getElementById('submitGeral').disabled = true;
        document.getElementById('anexoCom').disabled = true;
    }
    $("input#tab2").removeAttr('checked');
    $("input#tab1")[0].checked = true;

    if ($("div#bolinhaLateral")) {
        $("div#bolinhaLateral").remove();
    }
    $(function () {
        $('.wide.sidebar.teal').sidebar('toggle');
    });
    var hContent = $("#editor").height();
    var hWindow = $(window).height();
    if (hContent > hWindow) {
        document.getElementById("menuComentarios").style.width = 47;
    }
}


function addSrc() {
    var htmlEditor = tinyMCE.activeEditor.getContent();
    if (htmlEditor.length != 0) {
        var imagens = tinyMCE.activeEditor.dom.select('img');
        for (var j = 0; j < imagens.length; j++) {
            var srcOriginal = imagens[j].src;
            var splitSrc = srcOriginal.split('/');
            nomePadrao = splitSrc[splitSrc.length - 1];
            imagens[j].src = nomePadrao;
            imagens[j].setAttribute('data-mce-src', nomePadrao);
            imagens[j].src = '/webfolio/uploads/' + nomePadrao;
            imagens[j].setAttribute('data-mce-src', '/webfolio/uploads/' + nomePadrao);
        }
    }
}

function addTagImg() {
    var htmlEditor = tinyMCE.activeEditor.getContent();
    if (htmlEditor.length != 0) {
        var videos = tinyMCE.activeEditor.dom.select('img');
        for (var j = 0; j < videos.length; j++) {

            var srcOriginal = videos[j].src;//caminho da imagem
            console.log(srcOriginal);
//                var nomeVideo = srcOriginal.substr(0, srcOriginal.lastIndexOf('.'));
            var nomeVideo = srcOriginal.split('/');
            nomeVideo = nomeVideo[nomeVideo.length - 1];
            var nomeVideoSemExt = nomeVideo.substr(0, nomeVideo.lastIndexOf('.'));
            // nomeVideoSemExt = nomeVideoSemExt[nomeVideoSemExt.length - 1];
            var ext = srcOriginal.split('.');
            ext = ext[ext.length - 1];
            if (ext == 'mp4' || ext == 'avi') {
                var nomeImagem = ("" + nomeVideoSemExt + ".png");
                var splitSrc = srcOriginal.split('/');
                nomePadrao = splitSrc[splitSrc.length - 1];
                //   videos[j].id = "antigo";
                videos[j].src = '/webfolio/uploads/' + nomeImagem;
                videos[j].setAttribute('class', "mce-object mce-object-video");
                videos[j].setAttribute('data-mce-src', nomeImagem);
                videos[j].src = '/webfolio/uploads/' + nomeImagem;
                videos[j].setAttribute('data-mce-src', '/webfolio/uploads/' + nomeImagem);
            }
        }
    }
}
function removeSrc(caminho) {
    var htmlEditor = tinyMCE.activeEditor.getContent();
    if (htmlEditor.length != 0) {
        var imagens = tinyMCE.activeEditor.dom.select('img');
        var imgString = "<img src=";
        var startIndex = 0;
        var index;
        var indicesImg = [];
        var contImagens = 0;
        var t = imagens.length;

        while (((index = htmlEditor.indexOf(imgString, startIndex)) > -1) && (contImagens < t)) {
            if (imagens[contImagens].className != "mce-object mce-object-video") {
                indicesImg.push(index);
            }
            startIndex = index + imgString.length;
            contImagens++;
        }
        var tamanho = indicesImg.length;
        var i = 0;
        if (indicesImg.length != 0) {
            for (var k = 0; k < t; k++) {
                if (imagens[k].className != "mce-object mce-object-video") {
                    var srcAtual = imagens[k].src;
//                alert("imagem");
//                alert(srcAtual);
//                alert("indice " + indicesImg[i]);
                    var splitSrc = srcAtual.split('/');
                    var srcReduzida = splitSrc[splitSrc.length - 3] + '/' + splitSrc[splitSrc.length - 2] + '/' + splitSrc[splitSrc.length - 1];
                    var nomePadrao = splitSrc[splitSrc.length - 1];
                    htmlEditor = htmlEditor.replaceBetween((indicesImg[i] + 10), (indicesImg[i] + 11 + srcReduzida.length), nomePadrao);
                    for (var l = indicesImg.length; l > 0; l--) {
                        indicesImg.pop();
                    }
                    startIndex = 0;
                    contImagens = 0;
                    i++;
                    while (((index = htmlEditor.indexOf(imgString, startIndex)) > -1) && (contImagens < t)) {

//                    if (imagens.contImagens < imagens.length) {
                        if (imagens[contImagens].className != "mce-object mce-object-video") {
                            indicesImg.push(index);

                        }
                        startIndex = index + imgString.length;
                        contImagens++;
//                    }
                    }
                }
            }
        }
    }

    if (htmlEditor.length != 0) {
        var imagens = tinyMCE.activeEditor.dom.select('img');
        var t = imagens.length;
        console.debug(imagens);
        console.debug(imagens[0]);
        if (imagens.length > 0) {
            //     alert("meio");
            for (var j = 0; j < imagens.length; j++) {
                if (imagens[j].className == "mce-object mce-object-video") {
                    var srcOriginal = imagens[j].src;//caminho da imagem
                    console.log("é video");
                    var or = srcOriginal.split("/");
                    var final = or[or.length - 1];
                    var nomeSemEx = final.substr(0, final.lastIndexOf('.'));//erro aquii
                    var dataString = 'nomeFile=' + nomeSemEx + '';
                    var nomeVideo;
                    $.ajax({
                        type: "POST",
                        url: "" + caminho + "verificaExt",
                        data: dataString,
                        async: false,
                        cache: false,
                        success: function (resp) {
                            console.log("----------------" + resp);
                            nomeVideo = resp;


//                        imagens[j].src = nomeVideo;
//                        imagens[j].setAttribute('data-mce-src', nomeVideo);
//                        imagens[j].src = '/webfolio/uploads/' + nomeVideo;
//                        imagens[j].setAttribute('data-mce-src', '/webfolio/uploads' + nomeVideo);
                            //  imagens[j].replace(tagVideo);
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log("deu erro");
                        }
                    });
                    var imgString = "<img class=\"mce-object mce-object-video\" src=";
                    var startIndex = 0;
                    var index;
                    var indices = [];
                    contImagens = 0;
                    while (((index = htmlEditor.indexOf(imgString, startIndex)) > -1) && (contImagens < t)) {
                        if (imagens[contImagens].className == "mce-object mce-object-video") {
                            indices.push(index);
                        }
                        startIndex = index + imgString.length;
                        contImagens++;
                    }
                    var tamanho = indices.length;
                    if (indices.length != 0) {
                        for (var i = 0; i < tamanho; i++) {
                            var srcAtual = imagens[j].src;
                            console.log(imagens[j]);
                            var splitSrc = srcAtual.split('/');
                            var or = srcOriginal.split("/");
                            var final = or[or.length - 1];

                            var srcReduzida = 'webfolio/uploads/' + final;
                            htmlEditor = htmlEditor.replaceBetween((indices[i] + 46), (indices[i] + 47 + srcReduzida.length), nomeVideo);
                            console.log(htmlEditor);
                            for (var p = indices.length; p > 0; p--) {
                                indices.pop();
                            }
                            startIndex = 0;
                            contImagens = 0;
                            alert(htmlEditor);
                            while (((index = htmlEditor.indexOf(imgString, startIndex)) > -1) && (contImagens < t)) {
                                if (imagens[contImagens].className == "mce-object mce-object-video") {
                                    indices.push(index);
                                }
                                startIndex = index + imgString.length;
                                contImagens++;
                            }
                        }
                    }
                }
            }
        }
    }
    return htmlEditor;
}


String.prototype.replaceBetween = function (start, end, what) {
    return this.substring(0, start) + what + this.substring(end);
};
