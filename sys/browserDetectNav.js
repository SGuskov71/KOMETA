// Определение браузера и его версии
/*
 Среди объектов javascript есть объект navigator, у которого доступен метод UserAgent. Этот метод вернет строку, в которой содержится информация о браузере, его версии, ядре, операционной системе, в которой он запущен, а так же некоторых агентах и службах, встроенных в него. Примеры UserAgent:

Opera/9.64 (Windows NT 5.1; U; ru) Presto/2.1.1 Mozilla/5.0 (X11; U; Linux i686; it-IT; rv:1.9.0.2) Gecko/2008092313 Ubuntu/9.25 (jaunty) Firefox/3.8 Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 6.0; el-GR) Решение
Т.к. UserAgent возращает имя браузера и его версию для разных браузеров однотипно, поэтому можно составить регулярное выражение для получения нужной информации. Допустим UserAgent вернул такую строку:

Opera/9.64 (Windows NT 5.1; U; ru) Presto/2.1.1

В этой строке нужная информация — это Opera/9.64. Парсим строку и "вытаскиваем" нужное.

В функцию передаётся всего один параметр — chrAfterPoint, который определяет количество возвращаемых знаков версии. Т.е. функция вернет номер версии и chrAfterPoint знаков после запятой. Если параметр не будет передан — функция вернет все знаки после запятой.

Пример вызова функции:

                                // и выводить результат
function showBrowVer()          
{
var
data = browserDetectNav();      //вызываем функцию, параметр НЕ передаем,
                                //поэтому в результате получим все знаки версии после запятой

alert("Браузер: "+data[0]+", Версия: "+data[1]+"."+data[2]); //выводим результат
}
	
 */
function browserDetectNav(chrAfterPoint)
{
var
    UA=window.navigator.userAgent,       // содержит переданный браузером юзерагент
    //--------------------------------------------------------------------------------
	OperaB = /Opera[ \/]+\w+\.\w+/i,     //
	OperaV = /Version[ \/]+\w+\.\w+/i,   //	
	FirefoxB = /Firefox\/\w+\.\w+/i,     // шаблоны для распарсивания юзерагента
	ChromeB = /Chrome\/\w+\.\w+/i,       //
	SafariB = /Version\/\w+\.\w+/i,      //
	IEB = /MSIE *\d+\.\w+/i,             //
	SafariV = /Safari\/\w+\.\w+/i,       //
        //--------------------------------------------------------------------------------
	browser = new Array(),               //массив с данными о браузере
	browserSplit = /[ \/\.]/i,           //шаблон для разбивки данных о браузере из строки
	OperaV = UA.match(OperaV),
	Firefox = UA.match(FirefoxB),
	Chrome = UA.match(ChromeB),
	Safari = UA.match(SafariB),
	SafariV = UA.match(SafariV),
	IE = UA.match(IEB),
	Opera = UA.match(OperaB);
		
		//----- Opera ----
		if ((!Opera=="")&(!OperaV=="")) browser[0]=OperaV[0].replace(/Version/, "Opera")
				else 
					if (!Opera=="")	browser[0]=Opera[0]
						else
							//----- IE -----
							if (!IE=="") browser[0] = IE[0]
								else 
									//----- Firefox ----
									if (!Firefox=="") browser[0]=Firefox[0]
										else
											//----- Chrom ----
											if (!Chrome=="") browser[0] = Chrome[0]
												else
													//----- Safari ----
													if ((!Safari=="")&&(!SafariV=="")) browser[0] = Safari[0].replace("Version", "Safari");
//------------ Разбивка версии -----------

	var
            outputData;                                      // возвращаемый функцией массив значений
                                                             // [0] - имя браузера, [1] - целая часть версии
                                                             // [2] - дробная часть версии
	if (browser[0] != null) outputData = browser[0].split(browserSplit);
	if ((chrAfterPoint==null)&&(outputData != null)) 
		{
			chrAfterPoint=outputData[2].length;
			outputData[2] = outputData[2].substring(0, chrAfterPoint); // берем нужное ко-во знаков
			return(outputData);
		}
			else return(false);
}