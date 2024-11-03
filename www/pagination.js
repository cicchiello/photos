//let currentPage = pageNumberInput.value === "" ? 1 : pageNumberInput.value;
const RowsPerPage = 5;
const ColsPerRow = 6;
let totalPages;
const pageNumbers = document.getElementById("pageNumbers");


function paginateTable(initialCall, pageIdx) {
    currentPage = pageIdx+1;
    //console.log("DEBUG(paginateTable): currentPage: "+currentPage);
    const totalItems = totalTableEntries.innerHTML;  
    let table = document.getElementById("myTable");
    let rows = Array.from(table.rows).slice(1);
    totalPages = Math.trunc(totalItems/RowsPerPage/ColsPerRow)+1;
    
    rows.forEach(row=>row.style.display="none");
    
    let start = (currentPage - 1) * RowsPerPage;
    let end = start + RowsPerPage;
    rows.slice(start,end).forEach(row=>row.style.display = "");
    pageNumbers.innerHTML = "";
    createPageLink("<<",1);
    createPageLink("<",currentPage-1);
    
    let startPageNumber = currentPage < 5 ? 1 : (currentPage>totalPages-2?totalPages-4 : currentPage-2);
    let endPageNumber =totalPages<5 ? totalPages : (currentPage<=totalPages -2 ? startPageNumber+4 : totalPages);
    for (let i=startPageNumber;i<=endPageNumber;i++) {
	createPageLink(i,i);
    }
    createPageLink(">",currentPage+1);
    createPageLink(">>",totalPages);
    
    setActivePageNumber();
    
    from.innerHTML = (currentPage-1)*RowsPerPage*ColsPerRow+1;
    to.innerHTML = currentPage === totalPages ? totalItems : (currentPage)*RowsPerPage*ColsPerRow;
}


function setImages0(page, dburl, bookmark, onCompletion) {
    if (!bookmark) {
        var viewurl = dburl+"/_design/photos/_view/photo_ids?descending=false";
	fetch(viewurl)
	    .then(res => {
		if (!res.ok) {
		    console.log("ERROR(setImages0): network error");
		} else {
		    return res.json();
		}
	    })
	    .then(data => {
		var imageCnt = 0;
		for (var i = 0; i < data.rows.length; i++) {
		    if ((i >= page*RowsPerPage*ColsPerRow) && (i < (page+1)*RowsPerPage*ColsPerRow)) {
			var img = document.getElementById("image"+imageCnt);
			img.src = dburl+"/"+data.rows[i].id+"/thumbnail";
			img.setAttribute("data-objid", data.rows[i].id);
			img.setAttribute("data-firstrow", (page*RowsPerPage));
			imageCnt += 1;
		    }
		}
		while ((imageCnt > 0) && (imageCnt < RowsPerPage*ColsPerRow)) {
		    var img = document.getElementById("image"+imageCnt);
		    img.src = "img/transparent.png";
		    img.setAttribute("data-objid", null);
		    imageCnt += 1;
		}
		onCompletion();
	    })
	    .catch(error => {
		console.log("ERROR(setImages0): Fetch error: "+error);
	    });
    }
}


function changePage(e,pageNumber) {
    if((pageNumber == 0)||(pageNumber==totalPages+1)) return;
    e.preventDefault();
    pageNumberInput.value = "";

    var dburl = document.getElementById("dbUrl").innerHTML.trim();
    
    setImages0(pageNumber-1, dburl, null, function onCompletion() {
	paginateTable(false, pageNumber-1);
    });
}


function forceRedraw(element) {
    if (!element) { return; }

    var disp = element.style.display;  // get a copy of the previous display style
    element.offsetHeight; // just reference as part of redraw hack on some browsers 
    element.style.display = 'none';
    
    setTimeout(function(){
        element.style.display = disp;
    },50); // you can play with this timeout to make it as short as possible
}


function setActivePageNumber() {
    forceRedraw(document.getElementById("myTableParent"));
    document.querySelectorAll("#pageNumbers a").forEach(a=>{
        if(a.innerText == currentPage) {
            a.classList.add("active");
        }
    });
}


function createPageLink(linkText,pageNumber) {
    let pageLink = document.createElement("a");
    pageLink.href = "#";
    pageLink.innerHTML = linkText;
    pageLink.addEventListener("click",function(e){
	changePage(e,pageNumber);
    });
    pageNumbers.appendChild(pageLink);
}


goToPageButton.addEventListener("click",(e)=>{
    changePage(e,pageNumberInput.value);
});


